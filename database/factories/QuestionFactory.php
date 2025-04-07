<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\Topic;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class QuestionFactory extends Factory
{
  public function definition(): array
  {
    return [
      'courseable_id' => CourseSession::factory(),
      'courseable_type' => MorphMap::key(CourseSession::class),
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
        'courseable_id' => $this->faker->randomElement($couseSessionIDs),
        'courseable_type' => MorphMap::key(CourseSession::class),
        'topic_id' => $this->faker->randomElement($topicIDs)
      ]
    );
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'courseable_id' => CourseSession::factory()->institution($institution),
        'courseable_type' => MorphMap::key(CourseSession::class)
      ]
    );
  }

  public function courseable(Model $model): static
  {
    return $this->state(
      fn(array $attributes) => [
        'courseable_id' => $model->id,
        'courseable_type' => $model->getMorphClass(),
        ...$model->institution_id
          ? ['institution_id' => $model->institution_id]
          : []
      ]
    );
  }
}
