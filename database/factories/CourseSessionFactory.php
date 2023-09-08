<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseSessionFactory extends Factory
{
  public function definition(): array
  {
    $sessions = ['2001', '2002', '2003', '2004', '2005', '2006'];
    return [
      'course_id' => Course::factory(),
      'category' => '',
      'session' => $this->faker->randomElement($sessions)
    ];
  }
}
