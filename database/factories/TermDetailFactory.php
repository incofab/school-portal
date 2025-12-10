<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class TermDetailFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'academic_session_id' => AcademicSession::factory(),
      'institution_id' => Institution::factory(),
      'term' => TermType::First->value,
      'expected_attendance_count' => fake()->randomNumber(3),
      'for_mid_term' => false,
      'start_date' => now()
        ->addDay(2)
        ->toDateString(),
      'end_date' => now()
        ->addDay(20)
        ->toDateString(),
      'inactive_weekdays' => [5, 6],
      'special_active_days' => [
        [
          'date' => now()
            ->addDays(5)
            ->toDateString(),
          'reason' => 'Weekend workshop'
        ]
      ],
      'inactive_days' => [
        [
          'date' => now()
            ->addDays(10)
            ->toDateString(),
          'reason' => 'Public holiday'
        ]
      ],
      'next_term_resumption_date' => now()
        ->addDays(40)
        ->toDateString()
    ];
  }
}
