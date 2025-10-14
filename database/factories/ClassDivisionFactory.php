<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassDivisionFactory extends Factory
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
      'title' => fake()
        ->unique()
        ->sentence()
    ];
  }

  public function withInstitution(Institution $institution): static
  {
    return $this->state(
      fn(array $attr) => ['institution_id' => $institution->id]
    );
  }

  // function classification(Institution $institution)
  // {
  //   return $this->state(
  //     fn(array $attr) => ['institution_id' => $institution->id]
  //   )->afterCreating(
  //     fn(ClassDivision $classDivision) => Classification::factory()
  //       ->classDivision($classDivision)
  //       ->create()
  //   );
  // }
}
