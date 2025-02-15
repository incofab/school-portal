<?php

namespace Database\Factories;

use App\Enums\InstitutionSettingType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionSettingFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'key' => fake()->word(),
      'value' => fake()->sentence(),
      'institution_id' => Institution::factory()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => ['institution_id' => $institution->id]
    );
  }

  public function keyValue($key, $value): static
  {
    return $this->state(
      fn(array $attributes) => ['key' => $key, 'value' => $value]
    );
  }

  public function term(string $term = null): static
  {
    return $this->state(
      fn(array $attributes) => [
        'key' => InstitutionSettingType::CurrentTerm->value,
        'value' => $term ?? TermType::First->value
      ]
    );
  }

  public function academicSession(): static
  {
    return $this->state(
      fn(array $attributes) => [
        'key' => InstitutionSettingType::CurrentAcademicSession->value,
        'value' => $academicSession ?? AcademicSession::factory()
      ]
    );
  }
}
