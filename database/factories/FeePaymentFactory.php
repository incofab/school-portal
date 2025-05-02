<?php

namespace Database\Factories;

use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

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
      'amount' => fake()->randomNumber(4, true),
      'method' => fake()->word(),
      'reference' => Str::orderedUuid()
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
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
