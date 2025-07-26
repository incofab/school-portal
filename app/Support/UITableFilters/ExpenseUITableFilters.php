<?php

namespace App\Support\UITableFilters;

use App\Enums\TermType;
use Illuminate\Validation\Rules\Enum;

class ExpenseUITableFilters extends BaseUITableFilter
{
  protected array $sortableColumns = [
    'amount' => 'amount',
    'title' => 'title',
    'expenseDate' => 'expense_date',
  ];

  protected function extraValidationRules(): array
  {
    return [
      'title' => ['sometimes', 'string'],
      'term' => ['sometimes', new Enum(TermType::class)],
      'amount' => ['sometimes', 'numeric'],
      'academicSession' => ['sometimes', 'integer'],
      'expenseDate' => ['sometimes', 'string'],
      'expenseCategory' => ['sometimes', 'integer']
    ];
  }

  protected function generalSearch(string $search)
  {
    $this->joinExpenseCategory();
    $this->baseQuery
      ->where(fn($q) => $q
      ->where('expenses.title', 'LIKE', "%$search%")
      ->orWhere('expenses.amount', 'LIKE', "%$search%")
      ->orWhere('expense_categories.title', 'LIKE', "%$search%")
    );
    return $this;
  }

  public function joinExpenseCategory(): static
  {
    $this->callOnce(
      'joinExpenseCategory',
      fn() => $this->baseQuery->join('expense_categories', 'expense_categories.id', 'expenses.expense_category_id')
    );
    return $this;
  }

  protected function directQuery()
  {
    $this->baseQuery->when(
      $this->requestGet('title'),
      fn($q, $value) => $q->where('expenses.title', 'LIKE', "%$value%")
    )->when(
      $this->getTerm(),
      fn($q, $value) => $q->where('expenses.term', $value)
    )->when(
      $this->requestGet('amount'),
      fn($q, $value) => $q->where('expenses.amount', 'LIKE', "%$value%")
    )->when(
      $this->getAcademicSession(),
      fn($q, $value) => $q->where('expenses.academic_session_id', $value)
    )->when(
      $this->requestGet('expenseDate'),
      fn($q, $value) => $q->where('expenses.expense_date', $value)
    )->when(
      $this->requestGet('expenseCategory'),
      fn($q, $value) => $q->where('expenses.expense_category_id', $value)
    );
    return $this;
  }
}