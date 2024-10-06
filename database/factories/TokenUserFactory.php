<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class TokenUserFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'name' => fake()->name(),
      'email' => $this->faker->unique()->safeEmail,
      'phone' => $this->faker->unique()->phoneNumber,
      'reference' => Str::uuid(),
      'institution_id' => Institution::factory(),
      'user_id' => User::factory()
    ];
  }

  public function user(User $user): static
  {
    return $this->state(
      fn(array $attributes) => [
        'user_id' => $user->id
      ]
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
