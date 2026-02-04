<?php

namespace Database\Factories;

use App\Models\InternalNotification;
use App\Models\InternalNotificationRead;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternalNotificationReadFactory extends Factory
{
  protected $model = InternalNotificationRead::class;

  public function definition(): array
  {
    return [
      'internal_notification_id' => InternalNotification::factory(),
      'reader_type' => MorphMap::key(User::class),
      'reader_id' => User::factory(),
      'read_at' => now(),
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

  public function reader($model): static
  {
    return $this->state(fn() => [
      'reader_type' => $model->getMorphClass(),
      'reader_id' => $model->id
    ]);
  }
}
