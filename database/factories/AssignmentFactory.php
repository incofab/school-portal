<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Assignment>
 */
class AssignmentFactory extends Factory
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
      'course_teacher_id' => CourseTeacher::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'term' => TermType::First->value,
      'max_score' => fake()->randomNumber(2),
      'content' => fake()->sentence(),
      'expires_at' => now()->addDays(10)
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'course_teacher_id' => CourseTeacher::factory()->withInstitution($institution),
        'institution_id' => $institution->id,
      ]
    );
  }
}