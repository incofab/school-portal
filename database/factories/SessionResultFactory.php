<?php

namespace Database\Factories;

use App\Actions\CourseResult\GetGrade;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class SessionResultFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $result = mt_rand(20, 100);
    return [
      'institution_id' => Institution::factory(),
      'student_id' => Student::factory(),
      'classification_id' => Classification::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'result' => $result,
      'average' => $result,
      'grade' => GetGrade::run($result),
      'remark' => fake()->sentence()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'student_id' => Student::factory()->withInstitution($institution),
        'classification_id' => Classification::factory()->withInstitution(
          $institution
        )
      ]
    );
  }

  public function academicSession(AcademicSession $academicSession): static
  {
    return $this->state(
      fn(array $attributes) => ['academic_session_id' => $academicSession->id]
    );
  }

  public function classification(Classification $classification): static
  {
    return $this->state(
      fn(array $attributes) => [
        'classification_id' => $classification->id,
        'institution_id' => $classification->institution_id,
        'student_id' => Student::factory()->withInstitution(
          $classification->institution
        )
      ]
    );
  }
}
