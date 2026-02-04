<?php

namespace App\Support\Notifications;

use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\InstitutionUser;
use App\Models\InternalNotification;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Collection;

/**
 * @deprecated
 * Too much processing done here for something that's not so important
 */
class NotificationRecipientsResolver
{
  public function recipients(InternalNotification $notification): Collection
  {
    $notification->loadMissing('targets', 'reads');

    $resolved = [];

    foreach ($notification->targets as $target) {
      $recipients = $notification->institution_id
        ? $this->institutionRecipients($notification->institution_id, $target)
        : $this->globalRecipients($target);

      foreach ($recipients as $recipient) {
        $key = "{$recipient['reader_type']}:{$recipient['reader_id']}";
        $resolved[$key] = $recipient;
      }
    }

    $readMap = $notification->reads->mapWithKeys(
      fn($read) => ["{$read->reader_type}:{$read->reader_id}" => $read]
    );

    return collect($resolved)
      ->map(function ($recipient, $key) use ($readMap) {
        $read = $readMap->get($key);
        $recipient['is_read'] = (bool) $read;
        $recipient['read_at'] = $read?->read_at;
        return $recipient;
      })
      ->values();
  }

  public function summary(InternalNotification $notification): array
  {
    $recipients = $this->recipients($notification);
    return [
      'read_count' => $recipients->where('is_read', true)->count(),
      'recipient_count' => $recipients->count()
    ];
  }

  private function institutionRecipients(int $institutionId, $target): array
  {
    $targetType = $target->notifiable_type;
    $targetId = $target->notifiable_id;

    if ($targetType === MorphMap::key(InstitutionUser::class)) {
      return $this->formatInstitutionUsers(
        InstitutionUser::query()
          ->where('institution_id', $institutionId)
          ->whereKey($targetId)
          ->with('user')
          ->get()
      );
    }

    if ($targetType === MorphMap::key(Student::class)) {
      $student = Student::query()
        ->whereKey($targetId)
        ->whereHas(
          'institutionUser',
          fn($q) => $q->where('institution_id', $institutionId)
        )
        ->with('institutionUser.user')
        ->first();

      if (!$student || !$student->institutionUser) {
        return [];
      }

      return $this->formatInstitutionUsers(
        collect([$student->institutionUser])
      );
    }

    if ($targetType === MorphMap::key(Classification::class)) {
      return $this->formatInstitutionUsers(
        InstitutionUser::query()
          ->where('institution_id', $institutionId)
          ->whereHas(
            'student',
            fn($q) => $q->where('classification_id', $targetId)
          )
          ->with('user')
          ->get()
      );
    }

    if ($targetType === MorphMap::key(ClassificationGroup::class)) {
      return $this->formatInstitutionUsers(
        InstitutionUser::query()
          ->where('institution_id', $institutionId)
          ->whereHas(
            'student.classification',
            fn($q) => $q->where('classification_group_id', $targetId)
          )
          ->with('user')
          ->get()
      );
    }

    if ($targetType === MorphMap::key(User::class)) {
      return $this->formatInstitutionUsers(
        InstitutionUser::query()
          ->where('institution_id', $institutionId)
          ->where('user_id', $targetId)
          ->with('user')
          ->get()
      );
    }

    return [];
  }

  private function globalRecipients($target): array
  {
    if ($target->notifiable_type !== MorphMap::key(User::class)) {
      return [];
    }

    $user = User::query()->find($target->notifiable_id);

    if (!$user) {
      return [];
    }

    return [
      [
        'reader_type' => MorphMap::key(User::class),
        'reader_id' => $user->id,
        'name' => $user->full_name,
        'recipient_type' => 'User'
      ]
    ];
  }

  private function formatInstitutionUsers(Collection $institutionUsers): array
  {
    return $institutionUsers
      ->filter(fn($institutionUser) => !empty($institutionUser->user))
      ->map(
        fn($institutionUser) => [
          'reader_type' => MorphMap::key(InstitutionUser::class),
          'reader_id' => $institutionUser->id,
          'name' => $institutionUser->user->full_name,
          'recipient_type' => 'Institution User'
        ]
      )
      ->values()
      ->all();
  }
}
