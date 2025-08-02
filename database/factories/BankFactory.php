<?php

namespace Database\Factories;

use App\Models\Bank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = Bank::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'country_code' => fake()
        ->optional()
        ->countryCode(),
      'bank_name' => fake()->company() . ' National Bank',
      'bank_code' => fake()->numerify('####'),
      'support_account_verification' => fake()->boolean()
    ];
  }
}
