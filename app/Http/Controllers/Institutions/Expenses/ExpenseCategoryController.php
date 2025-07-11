<?php

namespace App\Http\Controllers\Institutions\Expenses;

use App\Actions\DownloadResult;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassificationGroup;
use App\Models\Event;
use App\Models\EventCourseable;
use App\Models\ExpenseCategory;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
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
      'expenseCategories' => paginateFromRequest($query->latest('id')),
    ]);
  }

  function store(Institution $institution)
  {
    $data = request()->validate([
      'title' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'title')->where('institution_id', $institution->id)],
      'description' => ['nullable', 'string']
    ]);

    $institution->expenseCategories()->create($data);
    return $this->ok();
  }

  public function update(Institution $institution, ExpenseCategory $expenseCategory)
  {
    $data = request()->validate([
      'title' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'title')->where('institution_id', $institution->id)->ignore($expenseCategory->id)],
      'description' => ['nullable', 'string']
    ]);

    $expenseCategory->fill($data)->save();
    return $this->ok();
  }

  function destroy(Institution $institution, ExpenseCategory $expenseCategory)
  {
    $expenseCategory->delete();
    return $this->ok();
  }
}
