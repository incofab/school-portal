<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
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
      'event_id' => Event::factory(),
      'student_id' => Student::factory(),
      'external_reference' => fake()->uniqid(),
      'exam_no' => fake()
        ->unique()
        ->numerify('###########'),
      'status' => ExamStatus::Active,
      'num_of_questions' => 40
    ];
  }
}
