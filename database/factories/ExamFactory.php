<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Models\Event;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

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
      // 'student_id' => Student::factory(),
      'external_reference' => Str::uuid(),
      'exam_no' => fake()
        ->unique()
        ->numerify('###########'),
      'num_of_questions' => 40,
      'status' => ExamStatus::Pending,
      'time_remaining' => 0,
      'start_time' => now(),
      'pause_time' => null,
      'end_time' => now()->addMinutes(60)
    ];
  }

  public function started(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'status' => ExamStatus::Active,
        'time_remaining' => 0,
        'start_time' => now(),
        'pause_time' => null,
        'end_time' => now()->addMinutes(60)
      ]
    );
  }

  public function paused(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'status' => ExamStatus::Paused,
        'time_remaining' => 30 * 60,
        'start_time' => null,
        'pause_time' => now(),
        'end_time' => null
      ]
    );
  }

  public function ended(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'status' => ExamStatus::Ended,
        'time_remaining' => 0,
        'start_time' => now()->subMinutes(30),
        'pause_time' => null,
        'end_time' => now()
      ]
    );
  }

  public function status(ExamStatus $examStatus = ExamStatus::Pending): static
  {
    return $this->state(fn(array $attributes) => ['status' => $examStatus]);
  }

  public function event(Event $event): static
  {
    return $this->state(
      fn(array $attributes) => [
        'event_id' => $event->id,
        'institution_id' => $event->institution_id,
        'student_id' => Student::factory()->withInstitution($event->institution)
      ]
    );
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'event_id' => Event::factory()->institution($institution),
        'student_id' => Student::factory()->withInstitution($institution)
      ]
    );
  }
}
