<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicSession>
 */
class AcademicSessionFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'title' => fake()
        ->unique()
        ->sentence()
    ];
  }

  public function deleted(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'deleted_at' => now()
      ]
    );
  }
}
