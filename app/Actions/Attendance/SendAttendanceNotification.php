<?php

namespace App\Actions\Attendance;

use App\Actions\Messages\RecordMessage;
use App\Enums\AttendanceNotificationType;
use App\Enums\AttendanceType;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Attendance;
use App\Models\Institution;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging\MessageDispatcher;
use App\Services\Messaging\Whatsapp\PhoneNumberNormalizer;
use App\Support\Res;
use App\Support\SettingsHandler;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAttendanceNotification
{
  private const SUBJECT = 'Daily Attendance';

  public function __construct(
    private AttendanceNotificationMessage $messageTemplate,
    private PhoneNumberNormalizer $phoneNumberNormalizer
  ) {
  }

  public function run(Attendance $attendance, AttendanceType $event): Res
  {
    $attendance->loadMissing(
      'institution.createdBy',
      'institutionUser.student.user',
      'institutionUser.student.guardian',
      'institutionUser.student.guardianStudents.guardian'
    );

    $institution = $attendance->institution;
    if (!$institution) {
      return failRes('failed');
    }

    $settings = SettingsHandler::makeFromInstitution($institution);
    $notification = $settings->getAttendanceNotification();

    if (
      $notification === AttendanceNotificationType::None ||
      !$this->shouldSend($notification, $event)
    ) {
      return failRes('disabled');
    }

    if ($this->alreadyNotified($attendance, $event)) {
      return failRes('skipped');
    }

    $channel = $settings->getPreferredMessageOption();
    $contact = $this->guardianContact($attendance, $channel);

    if (!$contact) {
      return failRes('skipped');
    }

    $sender = $institution->createdBy;
    if (!$sender) {
      return failRes('failed');
    }

    $body = $this->messageTemplate->render(
      $attendance,
      $this->date($attendance)
    );
    $message = $this->recordMessage(
      $institution,
      $sender,
      $attendance,
      $contact,
      $channel,
      $body,
      $event
    );

    try {
      (new MessageDispatcher($institution))->dispatch(
        collect([$contact]),
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

      return failRes('failed');
    }

    return successRes('sent');
  }

  private function shouldSend(
    AttendanceNotificationType $notification,
    AttendanceType $event
  ): bool {
    return match ($notification) {
      AttendanceNotificationType::CheckIn => $event === AttendanceType::In,
      AttendanceNotificationType::CheckInAndOut => true,
      AttendanceNotificationType::CheckOut => $event === AttendanceType::Out,
      AttendanceNotificationType::None => false
    };
  }

  private function alreadyNotified(
    Attendance $attendance,
    AttendanceType $event
  ): bool {
    return Message::query()
      ->where('institution_id', $attendance->institution_id)
      ->where('messageable_type', $attendance->getMorphClass())
      ->where('messageable_id', $attendance->id)
      ->where('subject', self::SUBJECT)
      ->get()
      ->contains(
        fn(Message $message) => ($message->meta['attendance_event'] ?? null) ===
          $event->value
      );
  }

  private function guardianContact(
    Attendance $attendance,
    NotificationChannelsType $channel
  ): ?string {
    $student = $attendance->institutionUser?->student;
    $guardians = collect([
      $student?->guardian,
      ...$student?->guardianStudents?->map->guardian->all() ?? []
    ])->filter();

    if ($channel === NotificationChannelsType::Email) {
      return $guardians
        ->pluck('email')
        ->first(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
    }

    foreach (
      [$student?->guardian_phone, ...$guardians->pluck('phone')->all()]
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
    string $contact,
    NotificationChannelsType $channel,
    string $body,
    AttendanceType $event
  ): Message {
    return (new RecordMessage($institution, $sender, [
      'subject' => self::SUBJECT,
      'body' => $body,
      'channel' => $channel->value,
      'meta' => [
        'attendance_notification' => true,
        'attendance_event' => $event->value,
        'attendance_date' => $this->date($attendance)->toDateString(),
        'attendance_id' => $attendance->id,
        'institution_user_id' => $attendance->institution_user_id,
        'student_id' => $attendance->institutionUser?->student?->id,
        'guardian_contact' => $contact
      ]
    ]))
      ->forSingle($contact)
      ->save($attendance);
  }

  private function date(Attendance $attendance): Carbon
  {
    return (
      $attendance->signed_in_at ??
      ($attendance->signed_out_at ?? now())
    )->copy();
  }
}
