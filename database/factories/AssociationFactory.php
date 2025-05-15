<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\Association;
use App\Models\UserAssociation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssociationFactory extends Factory
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
      'title' => fake()->word(),
      'description' => fake()->paragraph()
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => ['institution_id' => $institution->id]
    );
  }

  public function userAssociation($count = 1)
  {
    return $this->afterCreating(
      fn(Association $association) => UserAssociation::factory($count)
        ->association($association)
        ->create()
    );
  }
}
