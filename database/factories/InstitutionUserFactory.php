<?php

namespace Database\Factories;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
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

  public function admin(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'role' => InstitutionUserType::Admin->value
      ]
    );
  }

  public function teacher(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'role' => InstitutionUserType::Teacher->value
      ]
    );
  }

  public function student(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'role' => InstitutionUserType::Student->value,
        'institution_id' => $institution->id
      ]
    )->afterCreating(
      fn(
        InstitutionUser $institutionUser
      ) => Student::factory()->withInstitution(
        $institutionUser->institution,
        null,
        $institutionUser
      )
    );
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
