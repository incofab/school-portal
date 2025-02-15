<?php

namespace Database\Factories;

use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Models\InstitutionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceListFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'institution_group_id' => InstitutionGroup::factory(),
      'payment_structure' => fake()->randomElement(PaymentStructure::cases())->value,
      'type' => fake()->randomElement(PriceType::cases())->value,
      'amount' => fake()->numberBetween(600, 3600)
    ];
  }
  function type($type = PriceType::ResultChecking)
  {
    return $this->state(fn($q) => ['type' => $type->value]);
  }
}
