<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * This is rarely used because Assessments are also seeded when institution is created
 */
class ResultCommentTemplateFactory extends Factory
{
  // This is rarely used because Assessments are also seeded when institution is created
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'comment' => fake()->sentence,
      'comment_2' => fake()->sentence,
      'grade' => fake()->randomElement(['A', 'B', 'C', 'D', 'E', 'F']),
      'grade_label' => fake()->word,
      'min' => 0,
      'max' => 100,
      'type' => null
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution
      ]
    );
  }
}
