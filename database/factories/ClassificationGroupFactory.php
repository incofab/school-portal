<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassificationGroupFactory extends Factory
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
        ->sentence()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id
      ]
    );
  }
  function classification(Institution $institution)
  {
    return $this->state(
      fn(array $attributes) => ['institution_id' => $institution->id]
    )->afterCreating(
      fn(ClassificationGroup $classificationGroup) => Classification::factory()
        ->classificationGroup($classificationGroup)
        ->create()
    );
  }
}
