<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class TopicFactory extends Factory
{
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'course_id' => Course::factory(),
      'title' => $this->faker->words(8, true),
      'description' => $this->faker->paragraph
    ];
  }
  function course(Course $course) {
    return $this->state(fn($attr) => [
      'course_id' => $course->id,
      'institution_id' => $course->institution_id,
    ]);
  }
}
