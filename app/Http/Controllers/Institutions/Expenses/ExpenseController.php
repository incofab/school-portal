<?php

namespace App\Http\Controllers\Institutions\Expenses;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\Institution;
use App\Support\Audit\FinancialActivityLogger;
use App\Support\UITableFilters\ExpenseUITableFilters;
use Inertia\Inertia;

class ExpenseController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Institution $institution)
  {
    $query = ExpenseUITableFilters::make(
      request()->all(),
      Expense::query()->select('expenses.*')
    )
      ->filterQuery()
      ->getQuery();
    $amountSum = (clone $query)->sum('expenses.amount');
    $amountCount = (clone $query)->count('expenses.amount');
    $query->with([
      'institutionUser.user',
      'academicSession',
      'expenseCategory'
    ]);

    return Inertia::render('institutions/expenses/list-expenses', [
      'expenses' => paginateFromRequest($query->latest('expenses.id')),
      'expense_total' => $amountSum,
      'expense_count' => $amountCount
    ]);
  }

  public function create(Institution $institution)
  {
    return Inertia::render('institutions/expenses/create-edit-expense', [
      'expenseCategories' => $institution->expenseCategories
    ]);
  }

  public function store(Institution $institution, StoreExpenseRequest $request)
  {
    $validatedData = $request->validated();
    $createdBy = currentInstitutionUser()->id;
    $expense = $institution
      ->expenses()
      ->create([...$validatedData, 'created_by' => $createdBy]);

    app(FinancialActivityLogger::class)->expenseDecision($expense, 'approved');

    return $this->ok();
  }

  public function destroy(Institution $institution, Expense $expense)
  {
    $expense->loadMissing(
      'institution',
      'expenseCategory',
      'institutionUser.user'
    );
    $oldValues = $expense->only([
      'title',
      'description',
      'amount',
      'academic_session_id',
      'term',
      'expense_date',
      'expense_category_id',
      'created_by'
    ]);

    $expense->delete();

    app(FinancialActivityLogger::class)->expenseDecision(
      $expense,
      'deleted',
      $oldValues
    );

    return $this->ok();
  }
}
