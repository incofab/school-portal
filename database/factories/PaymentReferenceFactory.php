<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Institution;
use Illuminate\Support\Str;
use App\Enums\Payments\PaymentStatus;
use App\Enums\Payments\PaymentPurpose;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentReferenceFactory extends Factory
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
      'user_id' => User::factory(),
      'reference' => Str::orderedUuid(),
      'amount' => fake()->numberBetween(600, 3600),
      'status' => PaymentStatus::Pending->value,
      'purpose' => fake()->randomElement(PaymentPurpose::cases())->value
    ];
  }

  function payable(Model $model)
  {
    return $this->state(
      fn($attr) => [
        'payable_id' => $model->id,
        'payable_type' => $model->getMorphClass()
      ]
    );
  }

  function paymentable(Model $model)
  {
    return $this->state(
      fn($attr) => [
        'paymentable_id' => $model->id,
        'paymentable_type' => $model->getMorphClass()
      ]
    );
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'user_id' => User::factory()->admin($institution)
      ]
    );
  }
}
