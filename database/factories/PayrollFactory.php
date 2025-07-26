<?php

namespace Database\Factories;

use App\Models\Payroll;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\PayrollSummary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
  protected $model = Payroll::class;

  public function definition(): array
  {
    $gross = fake()->randomFloat(2, 50000, 500000);
    $tax = fake()->randomFloat(2, 1000, 50000);
    $deductions = fake()->randomFloat(2, 1000, 20000);
    $bonuses = fake()->randomFloat(2, 1000, 10000);
    $net = $gross - $tax - $deductions + $bonuses;

    return [
      'institution_id' => Institution::factory(),
      'institution_user_id' => InstitutionUser::factory(),
      'tax' => $tax,
      'total_deductions' => $deductions,
      'total_bonuses' => $bonuses,
      'gross_salary' => $gross,
      'net_salary' => $net,
      'payroll_summary_id' => PayrollSummary::factory(),
      'meta' => null
    ];
  }

  public function institutionUser(InstitutionUser $institutionUser): static
  {
    return $this->state(function (array $attributes) use ($institutionUser) {
      return [
        'institution_user_id' => $institutionUser->id,
        'institution_id' => $institutionUser->institution->id,
        'payroll_summary_id' => PayrollSummary::factory()
          ->for($institutionUser->institution)
          ->create()
      ];
    });
  }
}
