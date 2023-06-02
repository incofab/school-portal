<?php

namespace Database\Factories;

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
      'code' => date('Y') . fake()->numerify('####'),
      'classification_id' => Classification::factory(),
      'guardian_phone' => fake()->phoneNumber()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    $institutionUser = InstitutionUser::factory()
      ->withInstitution($institution)
      ->create();
    return $this->withInstitutionUser($institutionUser);
    // return $this->state(function (array $attributes) use ($institutionUser) {
    //   return [
    //     'institution_user_id' => $institutionUser->id,
    //     'user_id' => $institutionUser->user_id
    //   ];
    // });
  }

  public function withInstitutionUser(InstitutionUser $institutionUser): static
  {
    $institutionUser = $institutionUser ?? InstitutionUser::factory()->create();
    return $this->state(
      fn(array $attributes) => [
        'institution_user_id' => $institutionUser->id,
        'user_id' => $institutionUser->user_id,
        'classification_id' => Classification::factory()->withInstitution(
          $institutionUser->institution
        )
      ]
    );
  }
}
