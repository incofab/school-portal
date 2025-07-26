<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\SalaryType;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalaryType>
 */
class SalaryTypeFactory extends Factory
{
  protected $model = SalaryType::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'title' => fake()->jobTitle,
      'description' => fake()->optional()->sentence,
      'type' => fake()->randomElement(TransactionType::cases())->value, // Adjust based on your TransactionType enum
      'parent_id' => null, // You can override this manually in tests to set a parent
      'percentage' => fake()
        ->optional()
        ->randomFloat(2, 0, 100)
    ];
  }
  function credit()
  {
    return $this->state(
      fn($attr) => ['type' => TransactionType::Credit->value]
    );
  }
  function debit()
  {
    return $this->state(fn($attr) => ['type' => TransactionType::Debit->value]);
  }
}
