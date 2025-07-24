<?php

namespace Database\Factories;

use App\Models\PayrollSummary;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollSummary>
 */
class PayrollSummaryFactory extends Factory
{
  protected $model = PayrollSummary::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'amount' => fake()->randomFloat(2, 10000, 1000000),
      'total_tax' => fake()->randomFloat(2, 1000, 50000),
      'total_deduction' => fake()->randomFloat(2, 1000, 30000),
      'total_bonuses' => fake()->randomFloat(2, 500, 20000),
      'evaluated_at' => fake()
        ->optional()
        ->dateTimeThisYear(),
      'month' => fake()->monthName,
      'year' => fake()->year
    ];
  }

  public function evaluated()
  {
    return $this->state(fn($attr) => ['evaluated_at' => now()]);
  }

  public function notEvaluated()
  {
    return $this->state(fn($attr) => ['evaluated_at' => null]);
  }
}
