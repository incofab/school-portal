<?php

namespace Database\Factories;

use App\Models\Fee;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class FeeCategoryFactory extends Factory
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
      'fee_id' => Fee::factory()
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'fee_id' => Fee::factory()->institution($institution)
      ]
    );
  }

  public function fee(Fee $fee): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $fee->institution_id,
        'fee_id' => $fee->id
      ]
    );
  }

  public function feeable(Model $model): static
  {
    return $this->state(
      fn(array $attributes) => [
        'feeable_type' => $model->getMorphClass(),
        'feeable_id' => $model->id
      ]
    );
  }
}
