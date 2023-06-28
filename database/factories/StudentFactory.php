<?php

namespace Database\Factories;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionUser;
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
      // 'institution_user_id' => InstitutionUser::factory()->student(),
      // 'user_id' => User::factory(null, ['other_names' => null])->student(),
      'code' =>
        date('Y') .
        fake()
          ->unique()
          ->numerify('####'),
      'classification_id' => Classification::factory(),
      'guardian_phone' => fake()->phoneNumber()
    ];
  }

  public function withInstitution(
    Institution $institution,
    Classification $classification = null
  ): static {
    return $this->state(function (array $attributes) use (
      $institution,
      $classification
    ) {
      $institutionUser = InstitutionUser::factory()
        ->withInstitution($institution)
        ->create(['role' => InstitutionUserType::Student]);
      return [
        'institution_user_id' => $institutionUser->id,
        'user_id' => $institutionUser->user_id,
        'classification_id' =>
          $classification ??
          Classification::factory()->withInstitution(
            $institutionUser->institution
          )
      ];
    });
  }
}
