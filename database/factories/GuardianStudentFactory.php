<?php

namespace Database\Factories;

use App\Enums\GuardianRelationship;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GuardianStudent>
 */
class GuardianStudentFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'relationship' => GuardianRelationship::Parent
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(function (array $attributes) use ($institution) {
      return [
        'institution_id' => $institution->id,
        'guardian_user_id' => User::factory()->guardian($institution),
        'student_id' => Student::factory()->withInstitution($institution)
      ];
    });
  }
}
