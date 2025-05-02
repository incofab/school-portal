<?php

namespace Database\Factories;

use App\Enums\InstitutionUserType;
use App\Enums\ManagerRole;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'other_names' => fake()->word(),
      'email' => $this->faker->unique()->safeEmail,
      'phone' => $this->faker->unique()->phoneNumber,
      'email_verified_at' => now(),
      'password' =>
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
      'remember_token' => Str::random(10)
    ];
  }

  public function student(?Institution $institution = null): static
  {
    return $this->institutionUser($institution, InstitutionUserType::Student);
  }

  public function teacher(?Institution $institution = null): static
  {
    return $this->institutionUser($institution, InstitutionUserType::Teacher);
  }

  public function admin(?Institution $institution = null): static
  {
    return $this->institutionUser($institution, InstitutionUserType::Admin);
  }

  public function guardian(?Institution $institution = null): static
  {
    return $this->institutionUser($institution, InstitutionUserType::Guardian);
  }

  public function institutionUser(
    ?Institution $institution = null,
    $role = InstitutionUserType::Admin
  ): static {
    return $this->afterCreating(
      fn(User $user) => $user->institutionUsers()->create([
        'institution_id' =>
          $institution->id ?? Institution::factory()->create()->id,
        'role' => $role
      ])
    );
  }

  public function adminManager(): static
  {
    return $this->afterCreating(
      fn(User $user) => $user->syncRoles(ManagerRole::Admin)
    );
  }
  public function partnerManager(): static
  {
    return $this->afterCreating(
      fn(User $user) => $user->syncRoles(ManagerRole::Partner)
    );
  }
}
