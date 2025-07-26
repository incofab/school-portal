<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseCategoryFactory extends Factory
{
  protected $model = ExpenseCategory::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'title' => fake()->words(2, true),
      'description' => fake()
        ->optional()
        ->sentence()
    ];
  }
}
