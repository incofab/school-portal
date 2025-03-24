<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Pin;
use App\Models\PinGenerator;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class PinGeneratorFactory extends Factory
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
      'user_id' => User::factory(),
      'num_of_pins' => fake()->randomDigitNotZero(),
      'comment' => fake()->sentence(),
      'reference' => Str::orderedUuid()
    ];
  }
  function withInstitution(Institution $institution)
  {
    return $this->state(
      fn($attr) => [
        'institution_id' => $institution->id,
        'user_id' => $institution->createdBy->id
      ]
    );
  }
  function pins()
  {
    return $this->afterCreating(
      fn(PinGenerator $pinGenerator) => Pin::factory($pinGenerator->num_of_pins)
        ->pinGenerator($pinGenerator)
        ->create()
    );
  }
}
