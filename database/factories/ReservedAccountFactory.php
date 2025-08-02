<?php

namespace Database\Factories;

use App\Enums\Payments\PaymentMerchantType;
use App\Models\ReservedAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReservedAccount>
 */
class ReservedAccountFactory extends Factory
{
  /**
   * The name of the factory's corresponding model.
   *
   * @var string
   */
  protected $model = ReservedAccount::class;

  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'merchant' => PaymentMerchantType::Monnify->value,
      'bank_name' => fake()->company() . ' Bank',
      'bank_code' => fake()->numerify('####'),
      'account_name' => fake()->name(),
      'account_number' => fake()
        ->unique()
        ->numerify('##########'), // 10 digit account number
      'reference' => fake()
        ->optional()
        ->uuid() // Unique reference string
    ];
  }
  function reservable(Model $model)
  {
    return $this->state(
      fn($attr) => [
        'reservable_type' => $model->getMorphClass(),
        'reservable_id' => $model->id
      ]
    );
  }
}
