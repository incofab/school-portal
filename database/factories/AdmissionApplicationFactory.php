<?php

namespace Database\Factories;

use App\Models\AdmissionApplication;
use App\Models\ApplicationGuardian;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class AdmissionApplicationFactory extends Factory
{

  public function configure()
  {
    return $this->afterCreating(function (AdmissionApplication $model) {
      ApplicationGuardian::factory()->admissionApplication($model)->create();
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
      'institution_id' => Institution::factory(),
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'other_names' => fake()->name(),
      'nationality' => fake()->country(),
      'reference' => Str::orderedUuid()->toString(),
      'photo' => fake()->imageUrl(),
    ];
  }
}