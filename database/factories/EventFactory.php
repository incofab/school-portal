<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
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
      'title' => fake()
        ->unique()
        ->sentence(),
      'description' => fake()->sentence(),
      'duration' => fake()->numberBetween(10 * 60, 60 * 60),
      'status' => EventStatus::Active->value,
      'num_of_activations' => fake()->randomNumber(2),
      'num_of_subjects' => fake()->randomNumber(1),
      'starts_at' => now()
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id
      ]
    );
  }

  public function notStarted(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'status' => EventStatus::Active->value,
        'starts_at' => now()->addMinutes(30)
      ]
    );
  }

  public function started(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'status' => EventStatus::Active->value,
        'starts_at' => now()->subMinutes(30)
      ]
    );
  }

  public function ended(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'status' => EventStatus::Ended->value,
        'starts_at' => now()->subMinutes(30)
      ]
    );
  }
}
