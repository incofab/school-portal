<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * This is rarely used because Assessments are also seeded when institution is created
 */
class AssessmentFactory extends Factory
{
  // This is rarely used because Assessments are also seeded when institution is created
  public function definition(): array
  {
    $desc = fake()->unique()->sentence;
    return [
      'institution_id' => Institution::factory(),
      'title' => Str::slug($desc),
      'description' => $desc,
      'max' => 20
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
