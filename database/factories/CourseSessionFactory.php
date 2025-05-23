<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\Instruction;
use App\Models\Passage;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseSessionFactory extends Factory
{
  public function definition(): array
  {
    $sessions = ['2001', '2002', '2003', '2004', '2005', '2006'];
    return [
      'institution_id' => Institution::factory(),
      'course_id' => Course::factory(),
      'category' => '',
      'session' => $this->faker->randomElement($sessions)
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'course_id' => Course::factory()->withInstitution($institution)
      ]
    );
  }

  public function course(Course $course): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $course->institution_id,
        'course_id' => $course
      ]
    );
  }

  public function questions($count = 2): static
  {
    return $this->afterCreating(
      fn(CourseSession $courseSession) => Question::factory($count)
        ->courseable($courseSession)
        ->create()
    );
  }

  public function passages($count = 2): static
  {
    return $this->afterCreating(
      fn(CourseSession $courseSession) => Passage::factory($count)
        ->courseable($courseSession)
        ->create()
    );
  }

  public function instructions($count = 2): static
  {
    return $this->afterCreating(
      fn(CourseSession $courseSession) => Instruction::factory($count)
        ->courseable($courseSession)
        ->create()
    );
  }
}
