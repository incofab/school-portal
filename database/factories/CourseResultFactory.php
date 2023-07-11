<?php

namespace Database\Factories;

use App\Actions\CourseResult\GetGrade;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseResult>
 */
class CourseResultFactory extends Factory
{
  public function configure()
  {
    return $this->afterCreating(function (CourseResult $courseResult) {
      $result = $courseResult->result - $courseResult->exam;
      $assessments = $courseResult->getAssessments();
      $assessmentCount = $assessments->count();

      $assessmentValues = [];
      $assessments->map(function ($assessment) use (
        $result,
        $assessmentCount,
        &$assessmentValues
      ) {
        return $assessmentValues[$assessment->raw_title] =
          $result / $assessmentCount;
      });
      $courseResult->fill(['assessment_values' => $assessmentValues])->save();
    });
  }

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    // $ca1 = fake()->randomElement(range(1, 15));
    // $ca2 = fake()->randomElement(range(1, 15));
    $result = mt_rand(41, 80);
    $exam = $result - mt_rand(10, 40);

    return [
      'institution_id' => Institution::factory(),
      'student_id' => Student::factory(),
      'teacher_user_id' => User::factory()->teacher(),
      'course_id' => Course::factory(),
      'classification_id' => Classification::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'term' => fake()->randomElement(TermType::cases()),
      // 'first_assessment' => $ca1,
      // 'second_assessment' => $ca2,
      'exam' => $exam,
      'result' => $result,
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
        'teacher_user_id' => User::factory()->teacher($institution),
        'course_id' => Course::factory()->withInstitution($institution),
        'classification_id' => Classification::factory()->withInstitution(
          $institution
        ),
        'academic_session_id' => AcademicSession::factory()
      ]
    );
  }
}
