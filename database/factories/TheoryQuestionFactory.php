<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\EventCourseable;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class TheoryQuestionFactory extends Factory
{
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'courseable_type' => (new CourseSession())->getMorphClass(),
      'courseable_id' => CourseSession::factory(),
      'question_no' => $this->faker->unique()->numberBetween(1, 10000),
      'question_sub_number' => $this->faker
        ->optional()
        ->randomElement(['a', 'b', 'c']),
      'question' => $this->faker->paragraph,
      'marks' => $this->faker->randomFloat(1, 1, 20),
      'answer' => $this->faker->paragraph,
      'marking_scheme' => $this->faker->paragraph
    ];
  }

  public function courseable(Model $courseable): static
  {
    $institutionId =
      $courseable instanceof EventCourseable
        ? $courseable->event->institution_id
        : $courseable->institution_id;

    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institutionId ?? Institution::factory(),
        'courseable_type' => $courseable->getMorphClass(),
        'courseable_id' => $courseable->id
      ]
    );
  }
}
