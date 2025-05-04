<?php

namespace Database\Factories;

use App\Actions\SeedSetupData;
use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
  public function configure()
  {
    return $this->afterCreating(function (Institution $model) {
      $model->createdBy->institutionUsers()->firstOrCreate(
        ['institution_id' => $model->id],
        [
          'role' => InstitutionUserType::Admin
        ]
      );
      SeedSetupData::run($model);
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
      'institution_group_id' => InstitutionGroup::factory(),
      'uuid' => Str::orderedUuid(),
      'code' => Institution::generateInstitutionCode(),
      'user_id' => User::factory(),
      'email' => fake()->unique()->safeEmail,
      'phone' => fake()->unique()->phoneNumber,
      'name' => fake()->unique()->company,
      'address' => fake()
        ->unique()
        ->address()
    ];
  }
}
