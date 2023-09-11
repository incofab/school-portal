<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenUserFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'name' => fake()->name(),
      'email' => $this->faker->unique()->safeEmail,
      'phone' => $this->faker->unique()->phoneNumber,
      'reference' => Str::uuid()
    ];
  }
}
