<?php

namespace Database\Factories;

use App\Models\PayrollAdjustment;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\PayrollSummary;
use App\Models\PayrollAdjustmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollAdjustment>
 */
class PayrollAdjustmentFactory extends Factory
{
  protected $model = PayrollAdjustment::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'institution_user_id' => InstitutionUser::factory(),
      'payroll_adjustment_type_id' => PayrollAdjustmentType::factory(),
      'payroll_summary_id' => PayrollSummary::factory(),
      'amount' => fake()->randomFloat(2, 1000, 20000),
      'reference' => fake()->uuid,
      'description' => fake()
        ->optional()
        ->sentence()
    ];
  }

  public function institutionUser(InstitutionUser $institutionUser): static
  {
    return $this->state(function (array $attributes) use ($institutionUser) {
      return [
        'institution_user_id' => $institutionUser->id,
        'institution_id' => $institutionUser->institution->id
      ];
    });
  }
}
