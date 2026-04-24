<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatMessageFactory extends Factory
{
  protected $model = ChatMessage::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'chat_thread_id' => ChatThread::factory(),
      'sender_user_id' => User::factory(),
      'body' => fake()->sentence(),
    ];
  }
}
