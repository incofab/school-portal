<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'uuid' => Str::orderedUuid(),
      'code' => Institution::generateInstitutionCode(),
      'user_id' => User::factory(),
      'email' => fake()->unique()->safeEmail,
      'phone' => fake()->unique()->phoneNumber,
      'name' => fake()->unique()->company,
      'address' => fake()
        ->unique()
        ->address()
    ];
  }
}
