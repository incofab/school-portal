<?php

namespace App\Http\Controllers\Institutions\SalaryAdjustments;

use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalaryAdjustmentRequest;
use App\Models\Payroll;
use App\Models\SalaryAdjustment;
use Inertia\Inertia;

class SalaryAdjustmentsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  //= Grab the SalaryAdjustments associated with a given Payroll.
  public function payrollSalaryAdjustments(Institution $institution, Payroll $payroll)
  {
    $payrollSummary = $payroll->payrollSummary;

    $institutionUserId = $payroll->institution_user_id;
    $month = $payrollSummary->month;
    $year = $payrollSummary->year;

    $query = SalaryAdjustment::where('institution_user_id', $institutionUserId)
      ->with(['adjustmentType', 'institutionUser.user'])
      ->where('month', $month)
      ->where('year', $year);

    return inertia('institutions/salary-adjustments/list-salary-adjustments', [
      'salaryAdjustments' => paginateFromRequest($query),
      'adjustmentTypes' => $institution->adjustmentTypes,
      'parentAdjustmentTypes' => $institution->parentAdjustmentTypes,
    ]);
  }

  public function index(Institution $institution)
  {
    $query = $institution->salaryAdjustments()
      ->with([
        'adjustmentType' => function ($query) {
          $query->with('parent');
        },
        'institutionUser.user'
      ])
      ->latest('id');

    return inertia('institutions/salary-adjustments/list-salary-adjustments', [
      'salaryAdjustments' => paginateFromRequest($query),
      'adjustmentTypes' => $institution->adjustmentTypes,
      'parentAdjustmentTypes' => $institution->parentAdjustmentTypes,
    ]);
  }

  /*  == NO LONGER IN USE -- REPLACED WITH A MODAL.
  public function create(Institution $institution)
  {
    return Inertia::render('institutions/salary-adjustments/create-edit-salary-adjustment', [
      'adjustmentTypes' => $institution->adjustmentTypes,
      'parentAdjustmentTypes' => $institution->parentAdjustmentTypes,
    ]);
  }

  public function edit(Institution $institution, SalaryAdjustment $salaryAdjustment)
  {
    return Inertia::render('institutions/salary-adjustments/create-edit-salary-adjustment', [
      'adjustmentTypes' => $institution->adjustmentTypes,
      'parentAdjustmentTypes' => $institution->parentAdjustmentTypes,
      'salaryAdjustment' => $salaryAdjustment->load('institutionUser.user')
    ]);
  }
  */

  public function store(Institution $institution, SalaryAdjustmentRequest $request)
  {
    $validatedData = $request->validated();

    /* == I commented out this block of code because it is practically possible for a staff to commit the same offence multiple times in a month (eg: coming late to school), and being charged for the same offence multiple times in the same month. ==
    
    //= Check and Prevent duplicate record
    $hasRecord = $institution->salaryAdjustments()
    ->where('adjustment_type_id', $validatedData['adjustment_type_id'])
    ->where('month', $validatedData['month'])
    ->where('year', $validatedData['year'])
    ->where('institution_user_id', $validatedData['institution_user_id']) 
    ->exists();
    
    abort_if($hasRecord, 403, 'A similar record already exist for this staff.');
    */

    $institution->salaryAdjustments()->create($validatedData);
    return $this->ok();
  }

  public function update(Institution $institution, SalaryAdjustmentRequest $request, SalaryAdjustment $salaryAdjustment)
  {
    $validatedData = $request->validated();

    //= Once a 'Salary Adjustment' has been paid, it should not be Edited or Deleted.
    $hasBeenPaid = $institution->payrollSummaries()
      ->where('month', $validatedData['month'])
      ->where('year', $validatedData['year'])
      ->exists();

    abort_if($hasBeenPaid, 403, 'Record can NOT be modified because the salary has already been Paid.');

    $salaryAdjustment->fill($validatedData)->save();
    return $this->ok();
  }

  public function destroy(Institution $institution, SalaryAdjustment $salaryAdjustment)
  {
    //= Once a 'Salary Adjustment' has been paid, it should not be Edited or Deleted.
    $hasBeenPaid = $institution->payrollSummaries()
      ->where('month', $salaryAdjustment->month)
      ->where('year', $salaryAdjustment->year)
      ->exists();

    abort_if($hasBeenPaid, 403, 'Record can NOT be deleted because the salary has already been Paid.');

    //=
    $salaryAdjustment->delete();
    return $this->ok();
  }
}
