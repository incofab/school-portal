<?php

namespace Database\Factories;

use App\Models\Salary;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\SalaryType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Salary>
 */
class SalaryFactory extends Factory
{
  protected $model = Salary::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'institution_user_id' => InstitutionUser::factory(),
      'salary_type_id' => SalaryType::factory(),
      'amount' => fake()->randomFloat(2, 1000, 50000),
      'description' => fake()->optional()->paragraph
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
