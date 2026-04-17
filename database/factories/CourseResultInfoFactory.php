<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseResultInfo>
 */
class CourseResultInfoFactory extends Factory
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
      'course_id' => Course::factory(),
      'classification_id' => Classification::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'term' => fake()->randomElement(TermType::cases()),

      'num_of_students' => fake()->numberBetween(1, 100),
      'total_score' => fake()->numberBetween(1, 100),
      'max_obtainable_score' => fake()->numberBetween(1, 100),
      'max_score' => fake()->numberBetween(11, 100),
      'min_score' => fake()->numberBetween(1, 10),
      'average' => fake()->numberBetween(1, 100)
    ];
  }

  public function withInstitution(
    Institution $institution,
    ?Classification $classification = null,
    ?Course $course = null,
    ?AcademicSession $academicSession = null
  ): static {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'course_id' => $course
          ? $course->id
          : Course::factory()->withInstitution($institution),
        'classification_id' => $classification
          ? $classification->id
          : Classification::factory()->withInstitution($institution),
        'academic_session_id' => $academicSession
          ? $academicSession->id
          : AcademicSession::factory()
      ]
    );
  }
}
