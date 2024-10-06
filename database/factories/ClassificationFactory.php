<?php

namespace Database\Factories;

use App\Models\ClassificationGroup;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassificationFactory extends Factory
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
      'title' => fake()
        ->unique()
        ->sentence(),
      'classification_group_id' => ClassificationGroup::factory()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'classification_group_id' => ClassificationGroup::factory()->withInstitution(
          $institution
        )
      ]
    );
  }

  public function classificationGroup(
    ClassificationGroup $classificationGroup
  ): static {
    return $this->state(
      fn(array $attributes) => [
        'classification_group_id' => $classificationGroup->id,
        'institution_id' => $classificationGroup->institution_id
      ]
    );
  }
}
