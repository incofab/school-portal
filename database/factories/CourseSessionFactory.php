<?php
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseSessionFactory extends Factory
{
  public function definition(): array
  {
    $couseIDs = \App\Models\Course::all('id')
      ->pluck('id')
      ->toArray();
    $sessions = ['2001', '2002', '2003', '2004', '2005', '2006'];

    return [
      'course_id' => $this->faker->randomElement($couseIDs),
      'category' => '',
      'session' => $this->faker->randomElement($sessions)
    ];
  }
}
