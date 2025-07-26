<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Actions\Payrolls\SalaryHandler;
use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalaryTypeRequest;
use App\Models\SalaryType;

class SalaryTypesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Institution $institution)
  {
    $query = $institution
      ->salaryTypes()
      ->with(['parent'])
      ->latest('id');

    return inertia('institutions/payrolls/list-salary-types', [
      'salaryTypes' => paginateFromRequest($query),
      'salaryTypesArray' => $query->get()
    ]);
  }

  public function store(Institution $institution, SalaryTypeRequest $request)
  {
    $validatedData = $request->validated();
    //= Check and Prevent duplicate record
    $hasRecord = $institution
      ->salaryTypes()
      ->where('title', $validatedData['title'])
      ->where('type', $validatedData['type'])
      ->exists();

    abort_if($hasRecord, 403, 'A similar record already exist.');

    $institution->salaryTypes()->create($validatedData);
    return $this->ok();
  }

  public function update(
    Institution $institution,
    SalaryTypeRequest $request,
    SalaryType $salaryType
  ) {
    $validatedData = $request->validated();
    $salaryType->fill($validatedData)->save();

    $salaries = $salaryType
      ->salaries()
      ->with('salaryType')
      ->get();

    // Update all existing adjustments that may have been affected by this
    foreach ($salaries as $key => $salary) {
      (new SalaryHandler($institution))->update(
        $salary,
        [
          'institution_user_id' => $salary->institution_user_id,
          'amount' => $salary->amount
        ],
        false
      );
    }

    return $this->ok();
  }

  public function destroy(Institution $institution, SalaryType $salaryType)
  {
    if (
      $salaryType->salaries->isNotEmpty() ||
      $salaryType->children->isNotEmpty()
    ) {
      return $this->message(
        'This record can not be deleted because it is associated with some Staff Salaries or other Salary Types.',
        403
      );
    }

    $salaryType->delete();
    return $this->ok();
  }
}
