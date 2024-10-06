<?php

namespace Database\Factories;

use App\Enums\PaymentInterval;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\ReceiptType;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeeFactory extends Factory
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
      'receipt_type_id' => fn($attr) => ReceiptType::factory()->institution(
        Institution::find($attr['institution_id'])
      ),
      'classification_group_id' => fn(
        $attr
      ) => ClassificationGroup::factory()->withInstitution(
        Institution::find($attr['institution_id'])
      ),
      'classification_id' => fn(
        $attr
      ) => Classification::factory()->classificationGroup(
        ClassificationGroup::find($attr['classification_group_id'])
      ),
      'title' => fake()
        ->unique()
        ->sentence(),
      'payment_interval' => fake()->randomElement(PaymentInterval::cases())
        ->value,
      'amount' => fake()->numberBetween(10 * 60, 60 * 60)
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id
      ]
    );
  }

  public function receiptType(ReceiptType $receiptType): static
  {
    return $this->state(
      fn(array $attributes) => [
        'receipt_type_id' => $receiptType->id,
        'institution_id' => $receiptType->institution_id
      ]
    );
  }

  public function classification(Classification $classification): static
  {
    return $this->state(
      fn(array $attributes) => [
        'classification_id' => $classification->id
      ]
    );
  }

  public function classificationGroup(
    ClassificationGroup $classificationGroup
  ): static {
    return $this->state(
      fn(array $attributes) => [
        'classification_group_id' => $classificationGroup->id
      ]
    );
  }
}
