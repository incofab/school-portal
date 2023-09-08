<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Event;
use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamCourseableFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'exam_id' => Exam::factory(),
      'courseable_type' => (new CourseSession())->getMorphClass(),
      'courseable_id' => CourseSession::factory(),
      'score' => fake()->randomNumber(10, 29),
      'num_of_questions' => 30
    ];
  }
}
