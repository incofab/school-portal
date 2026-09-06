<?php

namespace Database\Factories;

use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionUserFactory extends Factory
{
  public function configure()
  {
    return $this->afterCreating(function (InstitutionUser $model) {
      app(
        \App\Services\Institutions\InstitutionRoleService::class
      )->assignDefaultRole($model);
    });
  }

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
      'type' => fake()->randomElement(InstitutionUserType::cases()),
      'status' => InstitutionUserStatus::Active->value
    ];
  }

  public function user(User $user): static
  {
    return $this->state(fn(array $attributes) => ['user_id' => $user->id]);
  }

  public function type(InstitutionUserType $type): static
  {
    return $this->state(fn(array $attributes) => ['type' => $type->value]);
  }

  public function admin(): static
  {
    return $this->type(InstitutionUserType::Admin);
  }

  public function teacher(?Institution $institution = null): static
  {
    return $this->when(
      $institution,
      fn($q) => $q->withInstitution($institution)
    )->type(InstitutionUserType::Teacher);
  }

  public function student(Institution $institution): static
  {
    return $this->withInstitution($institution)
      ->type(InstitutionUserType::Student)
      ->afterCreating(
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
      fn(array $attributes) => ['institution_id' => $institution->id]
    );
  }
}
