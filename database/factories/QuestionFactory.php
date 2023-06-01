<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
  public function definition(): array
  {
    $couseSessionIDs = \App\Models\CourseSession::all('id')
      ->pluck('id')
      ->toArray();
    $topicIDs = \App\Models\Topic::all('id')
      ->pluck('id')
      ->toArray();

    return [
      'course_session_id' => $this->faker->randomElement($couseSessionIDs),
      'topic_id' => $this->faker->randomElement($topicIDs),
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
}
