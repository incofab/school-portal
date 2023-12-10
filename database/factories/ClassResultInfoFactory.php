<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassResultInfoFactory extends Factory
{
  /**
   * Define the model's default state.
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'classification_id' => Classification::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'term' => fake()->randomElement(TermType::cases()),
      'min_score' => 0,
      'max_score' => 0
    ];
  }

  public function classification(Classification $classification): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $classification->institution_id,
        'classification_id' => $classification->id
      ]
    );
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'classification_id' => Classification::factory()->withInstitution(
          $institution
        )
      ]
    );
  }
}
