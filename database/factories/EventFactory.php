<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Institution;
use Illuminate\Support\Str;
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
      'duration' => fake()->numberBetween(60 * 60, 2 * 60 * 60),
      'status' => EventStatus::Active,
      'num_of_activations' => fake()->randomNumber(10, 40),
      'num_of_subjects' => fake()->randomNumber(1, 4),
      'starts_at' => now()->addMinutes(30)
    ];
  }
}
