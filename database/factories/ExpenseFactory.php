<?php

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\Expense;
use App\Models\Institution;
use App\Models\AcademicSession;
use App\Models\ExpenseCategory;
use App\Models\InstitutionUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
  protected $model = Expense::class;

  public function definition(): array
  {
    return [
      'institution_id' => Institution::factory(),
      'title' => fake()->sentence(3),
      'description' => fake()
        ->optional()
        ->paragraph(),
      'amount' => fake()->randomFloat(2, 100, 5000),
      'academic_session_id' => AcademicSession::factory(),
      'term' => fake()->randomElement(TermType::cases())->value,
      'expense_date' => fake()->date(),
      'expense_category_id' => ExpenseCategory::factory(),
      'created_by' => InstitutionUser::factory()
    ];
  }
  function createdBy(InstitutionUser $institutionUser)
  {
    return $this->state(
      fn($attr) => [
        'created_by' => $institutionUser->id,
        'institution_id' => $institutionUser->institution_id
      ]
    );
  }
}
