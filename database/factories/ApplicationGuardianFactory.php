<?php

namespace Database\Factories;

use App\Enums\GuardianRelationship;
use App\Models\AdmissionApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

class ApplicationGuardianFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'admission_application_id' => AdmissionApplication::factory(),
      'first_name' => fake()->firstName(),
      'last_name' => fake()->lastName(),
      'other_names' => fake()->name(),
      'email' => fake()->email(),
      'phone' => fake()->numerify('############'),
      'relationship' => fake()->randomElement(GuardianRelationship::cases())
        ->value
    ];
  }

  public function admissionApplication(
    AdmissionApplication $admissionApplication
  ): static {
    return $this->state(
      fn(array $attributes) => [
        'admission_application_id' => $admissionApplication->id
      ]
    );
  }
}
