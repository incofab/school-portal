<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeePaymentFactory extends Factory
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
      'fee_id' => Fee::factory(),
      'receipt_id' => Receipt::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'user_id' => fn($attr) => User::factory()->student(
        Institution::find($attr['institution_id'])
      ),
      'fee_amount' => fake()->randomNumber(4, true),
      'amount_paid' => fake()->randomNumber(3, true),
      'amount_remaining' => fn($attr) => $attr['fee_amount'] -
        $attr['amount_paid'],

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
        'fee_id' => $fee->id
      ]
    );
  }

  public function receipt(Receipt $receipt): static
  {
    return $this->state(
      fn(array $attributes) => [
        'receipt_id' => $receipt->id
      ]
    );
  }
}
