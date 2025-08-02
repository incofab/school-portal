<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = BankAccount::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'bank_name' => fake()->company() . ' Bank',
      'bank_code' => fake()->numerify('####'),
      'account_name' => fake()->name(),
      'account_number' => fake()
        ->unique()
        ->numerify('##########'),
      'is_primary' => false
    ];
  }

  function accountable(Model $model)
  {
    return $this->state(
      fn($attr) => [
        'accountable_type' => $model->getMorphClass(),
        'accountable_id' => $model->id
      ]
    );
  }

  function isPrimary(bool $value = true)
  {
    return $this->state(fn($attr) => ['is_primary' => $value]);
  }
}
