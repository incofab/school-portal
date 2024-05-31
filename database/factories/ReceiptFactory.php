<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReceiptFactory extends Factory
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
      'user_id' => fn($attr) => User::factory()->student(
        Institution::find($attr['institution_id'])
      ),
      'receipt_type_id' => fn($attr) => ReceiptType::factory()->institution(
        Institution::find($attr['institution_id'])
      ),
      'classification_id' => fn(
        $attr
      ) => Classification::factory()->withInstitution(
        Institution::find($attr['institution_id'])
      ),
      'classification_group_id' => fn(
        $attr
      ) => ClassificationGroup::factory()->withInstitution(
        Institution::find($attr['institution_id'])
      ),
      'academic_session_id' => AcademicSession::factory(),
      'term' => fake()->randomElement(TermType::cases())->value,
      'reference' => Str::uuid(),
      'title' => fake()->sentence(),
      'total_amount' => fake()->randomNumber(5, true)
    ];
  }

  public function institution(Institution $institution): static
  {
    return $this->state(
      fn(array $attributes) => [
        'institution_id' => $institution->id,
        'user_id' => User::factory()->student($institution),
        'receipt_type_id' => ReceiptType::factory()->institution($institution),
        'classification_id' => Classification::factory()->withInstitution(
          $institution
        ),
        'classification_group_id' => ClassificationGroup::factory()->withInstitution(
          $institution
        )
      ]
    );
  }

  public function student(Student $student): static
  {
    return $this->state(
      fn(array $attributes) => [
        'user_id' => $student->user_id
      ]
    );
  }
}
