<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class RegistrationRequestFactory extends Factory
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
      'reference' => Str::orderedUuid(),
      'data' => [
        ...User::factory()
          ->make()
          ->only([
            'first_name',
            'last_name',
            'other_names',
            'email',
            'phone',
            'password'
          ]),
        'institution' => Institution::factory()
          ->make()
          ->only(['name', 'phone', 'email', 'address'])
      ]
    ];
  }

  public function partner(User $user): static
  {
    return $this->state(fn(array $attributes) => ['partner_user_id' => $user]);
  }
}
