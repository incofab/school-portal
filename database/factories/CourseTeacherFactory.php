<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\Course;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseTeacher>
 */
class CourseTeacherFactory extends Factory
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
      'user_id' => User::factory()->teacher(),
      'classification_id' => Classification::factory()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'course_id' => Course::factory()->withInstitution($institution),
        'user_id' => User::factory()->teacher($institution),
        'classification_id' => Classification::factory()->withInstitution(
          $institution
        )
      ]
    );
  }
}
