<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class AdmissionFormFactory extends Factory
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
      'title' => fake()->sentence(),
      'description' => fake()->paragraph(),
      'price' => fake()->randomNumber(3)
    ];
  }
}
