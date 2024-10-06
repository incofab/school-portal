<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionGroupFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'partner_user_id' => User::factory()->partnerManager(),
      'user_id' => User::factory(),
      'name' => fake()->name
    ];
  }

  public function partner(User $user): static
  {
    return $this->state(fn(array $attributes) => ['partner_user_id' => $user]);
  }

  public function user(User $user): static
  {
    return $this->state(fn(array $attributes) => ['user_id' => $user]);
  }
}
