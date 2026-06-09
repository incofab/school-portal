<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Actions\Payrolls\SalaryHandler;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalaryRequest;
use App\Models\Institution;
use App\Models\Salary;
use App\Support\Audit\FinancialActivityLogger;

class SalariesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Institution $institution)
  {
    $query = $institution
      ->salaries()
      ->with('salaryType.parent', 'institutionUser.user');

    return inertia('institutions/payrolls/list-salaries', [
      'salaries' => paginateFromRequest($query),
      'salaryTypes' => $institution
        ->salaryTypes()
        ->with('parent')
        ->get()
    ]);
  }

  public function store(Institution $institution, SalaryRequest $request)
  {
    $validatedData = $request->validated();
    (new SalaryHandler($institution))->create($validatedData);

    return $this->ok();
  }

  public function update(
    Institution $institution,
    SalaryRequest $request,
    Salary $salary
  ) {
    $validatedData = $request->validated();
    $salary->load('salaryType');
    (new SalaryHandler($institution))->update($salary, $validatedData);

    return $this->ok();
  }

  public function destroy(Institution $institution, Salary $salary)
  {
    $salary->loadMissing('institution', 'salaryType', 'institutionUser.user');
    $oldValues = $salary->only([
      'institution_user_id',
      'salary_type_id',
      'description',
      'amount'
    ]);

    $salary->delete();

    app(FinancialActivityLogger::class)->payrollItemChanged(
      $salary,
      'deleted',
      $oldValues
    );

    return $this->ok();
  }
}
