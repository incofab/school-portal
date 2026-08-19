<?php

namespace App\Actions\Attendance;

use App\Actions\Messages\RecordMessage;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Messaging\Whatsapp\PhoneNumberNormalizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDailyAttendanceNotifications
{
  private const SUBJECT = 'Daily Attendance';

  public function __construct(
    private AttendanceNotificationMessage $messageTemplate,
    private PhoneNumberNormalizer $phoneNumberNormalizer
  ) {
  }

  public function run(?Carbon $date = null): array
  {
    $date = ($date ?? now())->copy();
    $channel = $this->channel();
    $stats = [
      'processed' => 0,
      'sent' => 0,
      'skipped' => 0,
      'failed' => 0
    ];

    $this->attendanceQuery($date)->chunkById(100, function (
      Collection $attendances
    ) use ($date, $channel, &$stats) {
      foreach ($attendances as $attendance) {
        $stats['processed']++;

        $result = $this->sendForAttendance($attendance, $date, $channel);
        $stats[$result]++;
      }
    });

    return $stats;
  }

  private function sendForAttendance(
    Attendance $attendance,
    Carbon $date,
    NotificationChannelsType $channel
  ): string {
    if ($this->alreadyNotified($attendance, $date)) {
      return 'skipped';
    }

    $attendance->loadMissing(
      'institution.createdBy',
      'institutionUser.student.user',
      'institutionUser.student.guardian'
    );

    $phone = $this->guardianPhone($attendance);
    if (!$phone) {
      Log::info('Attendance notification skipped: guardian phone missing.', [
        'attendance_id' => $attendance->id,
        'institution_id' => $attendance->institution_id,
        'institution_user_id' => $attendance->institution_user_id,
        'date' => $date->toDateString()
      ]);

      return 'skipped';
    }

    $institution = $attendance->institution;
    $sender = $institution?->createdBy;
    if (!$institution || !$sender) {
      Log::warning('Attendance notification skipped: sender unavailable.', [
        'attendance_id' => $attendance->id,
        'institution_id' => $attendance->institution_id
      ]);

      return 'failed';
    }

    $body = $this->messageTemplate->render($attendance, $date);
    $message = $this->recordMessage(
      $institution,
      $sender,
      $attendance,
      $phone,
      $channel,
      $body,
      $date
    );

    try {
      (new MessageDispatcher($institution))->dispatch(
        collect([$phone]),
        $channel,
        $body,
        self::SUBJECT,
        $message
      );
    } catch (Throwable $exception) {
      $meta = $message->meta ?? [];

      $message
        ->fill([
          'status' => MessageStatus::Failed->value,
          'meta' => [...$meta, 'failure' => $exception->getMessage()]
        ])
        ->save();

      // Log::warning('Attendance notification dispatch failed.', [
      //   'attendance_id' => $attendance->id,
      //   'message_id' => $message->id,
      //   'channel' => $channel->value,
      //   'exception' => $exception->getMessage()
      // ]);

      return 'failed';
    }

    return 'sent';
  }

  private function attendanceQuery(Carbon $date)
  {
    return Attendance::query()
      ->with(
        'institution.createdBy',
        'institutionUser.student.user',
        'institutionUser.student.guardian'
      )
      ->where(function ($query) use ($date) {
        $query
          ->whereDate('signed_in_at', $date->toDateString())
          ->orWhereDate('signed_out_at', $date->toDateString());
      })
      ->orderBy('id');
  }

  private function alreadyNotified(Attendance $attendance, Carbon $date): bool
  {
    return Message::query()
      ->where('institution_id', $attendance->institution_id)
      ->where('messageable_type', $attendance->getMorphClass())
      ->where('subject', self::SUBJECT)
      ->get()
      ->contains(
        fn(Message $message) => ($message->meta['attendance_date'] ?? null) ===
          $date->toDateString() &&
          (int) ($message->meta['institution_user_id'] ?? 0) ===
            $attendance->institution_user_id
      );
  }

  private function guardianPhone(Attendance $attendance): ?string
  {
    $student = $attendance->institutionUser?->student;

    foreach (
      [$student?->guardian_phone, $student?->guardian?->phone]
      as $phone
    ) {
      $normalized = $this->phoneNumberNormalizer->normalize($phone);

      if ($normalized && preg_match('/^\d{10,15}$/', $normalized)) {
        return $phone;
      }
    }

    return null;
  }

  private function recordMessage(
    Institution $institution,
    User $sender,
    Attendance $attendance,
    string $phone,
    NotificationChannelsType $channel,
    string $body,
    Carbon $date
  ): Message {
    return (new RecordMessage($institution, $sender, [
      'subject' => self::SUBJECT,
      'body' => $body,
      'channel' => $channel->value,
      'meta' => [
        'attendance_notification' => true,
        'attendance_date' => $date->toDateString(),
        'attendance_id' => $attendance->id,
        'institution_user_id' => $attendance->institution_user_id,
        'student_id' => $attendance->institutionUser?->student?->id,
        'guardian_phone' => $phone
      ]
    ]))
      ->forSingle($phone)
      ->save($attendance);
  }

  private function channel(): NotificationChannelsType
  {
    $channel = config(
      'services.attendance-notification.channel',
      NotificationChannelsType::Sms->value
    );

    return NotificationChannelsType::tryFrom(strtolower((string) $channel)) ??
      NotificationChannelsType::Sms;
  }
}
