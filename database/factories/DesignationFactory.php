<?php

namespace Database\Factories;

use App\Models\Designation;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Designation> */
class DesignationFactory extends Factory
{
  protected $model = Designation::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'title' => fake()
        ->unique()
        ->jobTitle(),
      'description' => fake()
        ->optional()
        ->sentence(),
      'is_for_students' => true,
      'scope' => 'institution',
      'max_holders' => 1,
      'is_active' => true
    ];
  }
}
