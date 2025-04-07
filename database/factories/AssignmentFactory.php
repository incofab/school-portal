<?php

namespace Database\Factories;

use App\Enums\AssignmentStatus;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assignment;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Carbon\Carbon;
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
      'course_id' => Course::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'term' => TermType::First->value,
      'status' => AssignmentStatus::Active->value,
      'max_score' => fake()->randomNumber(2),
      'content' => fake()->sentence(),
      'expires_at' => now()->addDays(10),
      'institution_user_id' => InstitutionUser::factory()
    ];
  }

  // This is a method to define the relationship between Assignment and Classification
  public function withClassificationGroup(
    $count = 2,
    $classificationGroup = null
  ) {
    return $this->afterCreating(function (Assignment $assignment) use (
      $count,
      $classificationGroup
    ) {
      // Attach classifications to the assignment after it's created
      $institutionId = $assignment->institution_id;

      $query = Classification::factory();

      if (!empty($classificationGroup)) {
        $query->classificationGroup($classificationGroup);
      }

      $classifications = $query->count($count)->create();

      // $classifications = Classification::factory()
      //   ->count($count)
      //   ->create(); // Generate 2 classifications by

      // Attach classifications to the assignment
      $assignment->classifications()->attach(
        $classifications
          ->mapWithKeys(function ($classification) use ($institutionId) {
            return [
              $classification->id => ['institution_id' => $institutionId]
            ];
          })
          ->toArray()
      );
    });
  }

  public function withClassifications($classifications)
  {
    return $this->afterCreating(function (Assignment $assignment) use (
      $classifications
    ) {
      // Attach classifications to the assignment after it's created
      $institutionId = $assignment->institution_id;

      // Attach classifications to the assignment
      $assignment->classifications()->attach(
        $classifications
          ->mapWithKeys(function ($classification) use ($institutionId) {
            return [
              $classification->id => ['institution_id' => $institutionId]
            ];
          })
          ->toArray()
      );
    });
  }
}
