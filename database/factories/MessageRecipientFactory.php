<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User; // Example recipient model, adjust if needed
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class MessageRecipientFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = MessageRecipient::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'message_id' => Message::factory(),
      'recipient_contact' => fake()->email(), // Or phone number
      'recipient_type' => null, // Default to User, adjust as needed
      'recipient_id' => null
    ];
  }

  /**
   * Indicate the recipient is a specific model.
   */
  public function recipient(Model $model): static
  {
    return $this->state(
      fn(array $attributes) => [
        'recipient_type' => $model->getMorphClass(),
        'recipient_id' => $model->id,
        'recipient_contact' => null // Clear contact if morph is set
      ]
    );
  }

  /**
   * Indicate the recipient is a contact string (e.g., email or phone).
   */
  public function contact(string $contact): static
  {
    return $this->state(
      fn(array $attributes) => [
        'recipient_contact' => $contact,
        'recipient_type' => null, // Clear morph if contact is set
        'recipient_id' => null
      ]
    );
  }

  /**
   * Associate the recipient with a specific message.
   */
  public function message(Message $message): static
  {
    return $this->state(
      fn(array $attributes) => [
        'message_id' => $message->id,
        'institution_id' => $message->institution_id // Inherit institution from message
      ]
    );
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id
      ]
    );
  }
}
