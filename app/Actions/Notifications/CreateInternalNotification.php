<?php

namespace App\Actions\Notifications;

use App\Models\InternalNotification;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Model;

class CreateInternalNotification
{
  public function execute(
    Model $sender,
    array $targets,
    string $title,
    ?string $body = null,
    ?string $actionUrl = null,
    ?string $type = null,
    array $data = [],
    ?Institution $institution = null
  ): InternalNotification {
    $notification = new InternalNotification([
      'title' => $title,
      'body' => $body,
      'action_url' => $actionUrl,
      'type' => $type,
      'data' => $data,
      'institution_id' => $institution?->id
    ]);

    $notification->sender()->associate($sender);
    $notification->save();

    $normalizedTargets = $this->normalizeTargets($targets);
    if (!empty($normalizedTargets)) {
      $notification->targets()->createMany($normalizedTargets);
    }

    return $notification->fresh(['targets']);
  }

  private function normalizeTargets(array $targets): array
  {
    $normalized = [];

    foreach ($targets as $target) {
      if ($target instanceof Model) {
        $type = $target->getMorphClass();
        $id = $target->getKey();
      } elseif (is_array($target)) {
        $type = $target['type'] ?? null;
        $id = $target['id'] ?? null;
      } else {
        continue;
      }

      if (!$type || !$id) {
        continue;
      }

      $key = "{$type}:{$id}";
      $normalized[$key] = [
        'notifiable_type' => $type,
        'notifiable_id' => $id
      ];
    }

    return array_values($normalized);
  }
}
