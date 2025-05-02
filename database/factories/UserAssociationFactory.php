<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserAssociationFactory extends Factory
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
      'association_id' => Association::factory(),
      'institution_user_id' => InstitutionUser::factory(),
      'title' => fake()->word(),
      'description' => fake()->paragraph()
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'institution_user_id' => InstitutionUser::factory()->withInstitution(
          $institution
        ),
        'association_id' => Association::factory()->institution($institution)
      ]
    );
  }

  public function association(Association $association): static
  {
    return $this->state(
      fn(array $attributes) => [
        'association_id' => $association->id,
        'institution_id' => $association->institution_id,
        'institution_user_id' => InstitutionUser::factory()->withInstitution(
          $association->institution
        )
      ]
    );
  }
  public function institutionUser(InstitutionUser $institutionUser): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_user_id' => $institutionUser->id,
        'institution_id' => $institutionUser->institution_id,
        'association_id' => Association::factory()->institution(
          $institutionUser->institution
        )
      ]
    );
  }
}
