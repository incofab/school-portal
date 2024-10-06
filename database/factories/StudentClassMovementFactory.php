<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Classification>
 */
class StudentClassMovementFactory extends Factory
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
      'student_id' => Student::factory(),
      'source_classification_id' => Classification::factory(),
      'destination_classification_id' => Classification::factory(),
      'user_id' => User::factory(),
      'note' => fake()->sentence(),
      'batch_no' => uniqid()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'student_id' => Student::factory()->withInstitution($institution),
        'source_classification_id' => Classification::factory()->withInstitution(
          $institution
        ),
        'destination_classification_id' => Classification::factory()->withInstitution(
          $institution
        )
      ]
    );
  }

  public function toAlumni(): static
  {
    return $this->state(
      fn(array $attributes) => ['destination_classification_id' => null]
    );
  }

  public function fromAlumni(): static
  {
    return $this->state(
      fn(array $attributes) => ['source_classification_id' => null]
    );
  }
}
