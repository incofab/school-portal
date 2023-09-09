<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
  public function definition(): array
  {
    return [
      'course_session_id' => CourseSession::factory(),
      'topic_id' => Topic::factory(),
      'question_no' => rand(1, 50),
      'question' => $this->faker->paragraph,
      'option_a' => $this->faker->sentence,
      'option_b' => $this->faker->sentence,
      'option_c' => $this->faker->sentence,
      'option_d' => $this->faker->sentence,
      'option_e' => $this->faker->sentence,
      'answer' => $this->faker->randomElement(['A', 'B', 'C', 'D']),
      'answer_meta' => $this->faker->paragraph
    ];
  }

  public function forSeeding(): static
  {
    $couseSessionIDs = \App\Models\CourseSession::all('id')
      ->pluck('id')
      ->toArray();
    $topicIDs = \App\Models\Topic::all('id')
      ->pluck('id')
      ->toArray();
    return $this->state(
      fn(array $attributes) => [
        'course_session_id' => $this->faker->randomElement($couseSessionIDs),
        'topic_id' => $this->faker->randomElement($topicIDs)
      ]
    );
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'course_session_id' => CourseSession::factory()->institution(
          $institution
        )
      ]
    );
  }

  public function courseSession(CourseSession $courseSession): static
  {
    return $this->state(
      fn(array $attributes) => ['course_session_id' => $courseSession]
    );
  }
}
