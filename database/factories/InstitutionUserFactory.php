<?php

namespace Database\Factories;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionUserFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'user_id' => User::factory(),
      'institution_id' => Institution::factory(),
      'role' => fake()->randomElement(InstitutionUserType::cases())
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
}
