<?php

namespace Database\Factories;

use App\Enums\PaymentInterval;
use App\Models\Institution;
use App\Models\ReceiptType;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeFactory extends Factory
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
      'receipt_type_id' => fn($attr) => ReceiptType::factory()->institution(
        Institution::find($attr['institution_id'])
      ),
      'title' => fake()
        ->unique()
        ->sentence(),
      'payment_interval' => fake()->randomElement(PaymentInterval::cases())
        ->value,
      'amount' => fake()->numberBetween(10 * 60, 60 * 60)
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id
      ]
    );
  }

  public function receiptType(ReceiptType $receiptType): static
  {
    return $this->state(
      fn(array $attributes) => [
        'receipt_type_id' => $receiptType->id,
        'institution_id' => $receiptType->institution_id
      ]
    );
  }
}
