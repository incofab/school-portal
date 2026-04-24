<?php

namespace Database\Factories;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\ChatThreadRead;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChatThreadReadFactory extends Factory
{
  protected $model = ChatThreadRead::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'chat_thread_id' => ChatThread::factory(),
      'user_id' => User::factory(),
      'last_read_chat_message_id' => ChatMessage::factory(),
      'read_at' => now(),
    ];
  }
}
