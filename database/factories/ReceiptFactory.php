<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
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
      'user_id' => fn($attr) => User::factory()->student(
        Institution::find($attr['institution_id'])
      ),
      'fee_id' => fn($attr) => User::factory()->student(
        Institution::find($attr['institution_id'])
      ),
      'amount' => fake()->randomNumber(4, true),
      'amount_remaining' => 0,
      'amount_paid' => 0,
      'academic_session_id' => AcademicSession::factory(),
      'term' => fake()->randomElement(TermType::cases())->value
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'user_id' => User::factory()->student($institution),
        'fee_id' => Fee::factory()->institution($institution)
      ]
    );
  }

  public function fee(Fee $fee): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $fee->institution_id,
        'fee_id' => $fee->id,
        'academic_session_id' => $fee->academic_session_id,
        'term' => $fee->term
      ]
    );
  }

  public function student(Student $student): static
  {
    return $this->state(
      fn(array $attributes) => [
        'user_id' => $student->user_id
      ]
    );
  }
}
