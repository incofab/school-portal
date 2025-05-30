<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Event;
use App\Models\Exam;
use App\Support\MorphMap;
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
      'score' => fake()->randomNumber(2),
      'num_of_questions' => 30
    ];
  }

  public function exam(Exam $exam): static
  {
    return $this->state(
      fn(array $attributes) => [
        'exam_id' => $exam->id,
        'courseable_id' => CourseSession::factory()->institution(
          $exam->institution
        ),
        'courseable_type' => MorphMap::key(CourseSession::class)
      ]
    );
  }

  public function courseable(CourseSession $courseable): static
  {
    return $this->state(
      fn(array $attributes) => [
        'courseable_type' => $courseable->getMorphClass(),
        'courseable_id' => $courseable->id
      ]
    );
  }
}
