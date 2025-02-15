<?php

namespace Database\Factories;

use App\Enums\PriceLists\PaymentStructure;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResultPublicationFactory extends Factory
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
      'institution_group_id' => InstitutionGroup::factory(),
      'academic_session_id' => AcademicSession::factory(),
      'staff_user_id' => User::factory(),
      'term' => fake()->randomElement(TermType::cases())->value,
      'payment_structure' => fake()->randomElement(PaymentStructure::cases())->value,
      'num_of_results' => fake()->numberBetween(600, 1000),
      'amount' => fake()->numberBetween(600, 3600)
    ];
  }

  function institution(Institution $institution)
  {
    return $this->state(fn($attr) => [
      'institution_id' => $institution,
      'institution_group_id' => $institution->institution_group_id,
      'staff_user_id' => User::factory()->admin($institution),
    ]);
  }
}
