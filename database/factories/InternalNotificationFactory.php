<?php

namespace Database\Factories;

use App\Models\InternalNotification;
use App\Models\Institution;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;

class InternalNotificationFactory extends Factory
{
  protected $model = InternalNotification::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'sender_type' => MorphMap::key(User::class),
      'sender_id' => User::factory(),
      'type' => null,
      'title' => fake()->sentence(),
      'body' => fake()->paragraph(),
      'action_url' => null,
      'data' => null,
      'created_at' => now(),
      'updated_at' => now()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(fn() => ['institution_id' => $institution->id]);
  }

  public function sender($sender): static
  {
    return $this->state(
      fn() => [
        'sender_type' => $sender->getMorphClass(),
        'sender_id' => $sender->id
      ]
    );
  }
}
