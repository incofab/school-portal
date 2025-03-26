<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\Institution;
use App\Models\PinGenerator;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class PinFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'pin' => fake()->numerify('##########'),
      'institution_id' => Institution::factory(),
      'pin_generator_id' => PinGenerator::factory()
    ];
  }

  public function pinGenerator(PinGenerator $pinGenerator): static
  {
    return $this->state(
      fn(array $attributes) => [
        'pin_generator_id' => $pinGenerator->id
      ]
    )->withInstitution($pinGenerator->institution);
  }

  public function withInstitution(
    Institution $institution,
    bool $forStudent = false
  ): static {
    return $this->state(function (array $attributes) use (
      $institution,
      $forStudent
    ) {
      return [
        'institution_id' => $institution->id,
        ...$forStudent
          ? [
            'student_id' => Student::factory()->withInstitution($institution),
            'term' => TermType::First->value
          ]
          : []
      ];
    });
  }

  public function forStudent(Student $student): static
  {
    return $this->state(function (array $attributes) use ($student) {
      return ['student_id' => $student->id, 'term' => TermType::First->value];
    });
  }

  public function used(): static
  {
    return $this->state(fn(array $attributes) => ['used_at' => now()]);
  }
}
