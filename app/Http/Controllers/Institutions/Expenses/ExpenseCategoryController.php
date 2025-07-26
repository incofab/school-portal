<?php

namespace App\Http\Controllers\Institutions\Expenses;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except('search');
  }

  public function search()
  {
    return response()->json([
      'result' => ExpenseCategory::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->latest('title')
        ->get()
    ]);
  }

  function index(Institution $institution)
  {
    $query = $institution->expenseCategories();

    return Inertia::render('institutions/expenses/list-expense-categories', [
      'expenseCategories' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function store(Request $request, Institution $institution)
  {
    $data = $request->validate(ExpenseCategory::createRule());

    $institution->expenseCategories()->create($data);
    return $this->ok();
  }

  public function update(
    Request $request,
    Institution $institution,
    ExpenseCategory $expenseCategory
  ) {
    $data = $request->validate(ExpenseCategory::createRule($expenseCategory));
    $expenseCategory->fill($data)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, ExpenseCategory $expenseCategory)
  {
    $expenseCategory->delete();
    return $this->ok();
  }
}
