<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdmissionFormFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'title' => fake()->sentence(),
      'description' => fake()->paragraph(),
      'price' => fake()->randomNumber(3),
      'term' => fake()->randomElement(TermType::cases())->value
    ];
  }
}
