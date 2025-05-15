<?php

namespace Database\Factories;

use App\Enums\MessageRecipientCategory;
use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'sender_user_id' => User::factory(),
      'subject' => fake()->word(),
      'body' => fake()->paragraph(),
      'channel' => NotificationChannelsType::Email->value,
      'recipient_category' => MessageRecipientCategory::Single->value,
      'status' => MessageStatus::Sent->value,
      'sent_at' => fake()->word(),
      'meta' => fake()->word()
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'sender_user_id' => $institution->user_id
      ]
    );
  }

  public function messageRecipient($count = 1)
  {
    return $this->afterCreating(
      fn(Message $message) => MessageRecipient::factory($count)
        ->message($message)
        ->create()
    );
  }
}
