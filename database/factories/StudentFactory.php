<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'user_id' => User::factory(null, ['other_names' => null])->student(),
      'code' => date('Y') . fake()->numerify('####'),
      'classification_id' => Classification::factory(),
      'guardian_phone' => fake()->phoneNumber()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'classification_id' => Classification::factory()->withInstitution(
          $institution
        ),
        'user_id' => User::factory()->student($institution)
      ]
    );
  }
}
