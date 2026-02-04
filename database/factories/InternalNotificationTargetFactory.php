<?php

namespace Database\Factories;

use App\Models\InternalNotification;
use App\Models\InternalNotificationTarget;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternalNotificationTargetFactory extends Factory
{
  protected $model = InternalNotificationTarget::class;

  public function definition(): array
  {
    return [
      'internal_notification_id' => InternalNotification::factory(),
      'notifiable_type' => MorphMap::key(User::class),
      'notifiable_id' => User::factory(),
      'created_at' => now(),
      'updated_at' => now()
    ];
  }

  public function notification(InternalNotification $notification): static
  {
    return $this->state(fn() => [
      'internal_notification_id' => $notification->id
    ]);
  }

  public function notifiable($model): static
  {
    return $this->state(fn() => [
      'notifiable_type' => $model->getMorphClass(),
      'notifiable_id' => $model->id
    ]);
  }
}
