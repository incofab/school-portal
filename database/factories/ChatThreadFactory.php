<?php

namespace Database\Factories;

use App\Enums\ChatThreadType;
use App\Models\ChatThread;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatThreadFactory extends Factory
{
  protected $model = ChatThread::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'requester_user_id' => User::factory(),
      'target_user_id' => User::factory(),
      'type' => ChatThreadType::DirectUser->value,
      'target_role' => null,
      'last_message_preview' => null,
      'last_message_at' => null,
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(fn() => ['institution_id' => $institution->id]);
  }
}
