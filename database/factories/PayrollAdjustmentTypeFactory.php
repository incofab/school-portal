<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\PayrollAdjustmentType;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollAdjustmentType>
 */
class PayrollAdjustmentTypeFactory extends Factory
{
  protected $model = PayrollAdjustmentType::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'title' => fake()->words(2, true),
      'description' => fake()
        ->optional()
        ->sentence(),
      'type' => fake()->randomElement(TransactionType::cases())->value,
      'parent_id' => null,
      'percentage' => fake()
        ->optional()
        ->randomFloat(2, 0, 100)
    ];
  }

  function credit()
  {
    return $this->state(
      fn($attr) => ['type' => TransactionType::Credit->value]
    );
  }

  function debit()
  {
    return $this->state(fn($attr) => ['type' => TransactionType::Debit->value]);
  }
}
