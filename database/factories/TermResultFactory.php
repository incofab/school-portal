<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class TermResultFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'term' => TermType::First->value,
      'total_score' => fake()->randomNumber(2),
      'position' => fake()->randomDigit(),
      'average' => fake()->randomNumber(2),
      'remark' => fake()->sentence(),
      'academic_session_id' => AcademicSession::factory()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(function (array $attributes) use ($institution) {
      return [
        'institution_id' => $institution->id,
        'student_id' => Student::factory()->withInstitution($institution),
        'classification_id' => Classification::factory()->withInstitution(
          $institution
        )
      ];
    });
  }
  public function forStudent(Student $student): static
  {
    return $this->state(function (array $attributes) use ($student) {
      return [
        'student_id' => $student->id,
        'institution_id' => $student->institutionUser->institution->id,
        'classification_id' => $student->classification_id
      ];
    });
  }
}
