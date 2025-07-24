<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Actions\Payrolls\PayrollAdjustmentHandler;
use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollAdjustmentTypeRequest;
use App\Models\PayrollAdjustmentType;
use App\Models\PayrollSummary;

class PayrollAdjustmentTypesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  /** @deprecated No need for this function  */
  public function index(Institution $institution)
  {
    $query = $institution
      ->payrollAdjustmentTypes()
      ->with(['parent'])
      ->latest('id');

    return inertia('institutions/payrolls/list-payroll-adjustment-types', [
      'payrollAdjustmentTypes' => paginateFromRequest($query),
      'parentAdjustmentTypes' => $institution->parentAdjustmentTypes //AdjustmentTypes that are not children of another type.
    ]);
  }

  public function store(
    Institution $institution,
    PayrollAdjustmentTypeRequest $request
  ) {
    $validatedData = $request->validated();

    //= Check and Prevent duplicate record
    $hasRecord = $institution
      ->payrollAdjustmentTypes()
      ->where('title', $validatedData['title'])
      ->where('type', $validatedData['type'])
      ->exists();

    abort_if($hasRecord, 403, 'A similar record already exist.');

    $institution->payrollAdjustmentTypes()->create($validatedData);
    return $this->ok();
  }

  public function update(
    Institution $institution,
    PayrollAdjustmentTypeRequest $request,
    PayrollAdjustmentType $payrollAdjustmentType
  ) {
    $validatedData = $request->validated();
    $payrollAdjustmentType->fill($validatedData)->save();

    /** @var PayrollSummary $latestPayrollSummary */
    $latestPayrollSummary = PayrollSummary::query()
      ->notEvaluated()
      ->latest()
      ->first();
    $salaryAdJustments =
      $latestPayrollSummary
        ?->payrollAdjustments()
        ->where('payroll_adjustment_type_id', $payrollAdjustmentType->id)
        ->with('payrollAdjustmentType')
        ->get() ?? [];

    // Update all existing adjustments that may have been affected by this
    foreach ($salaryAdJustments as $key => $salaryAdJustment) {
      (new PayrollAdjustmentHandler($institution))->update(
        $salaryAdJustment,
        [
          'institution_user_id' => $salaryAdJustment->institution_user_id,
          'amount' => $salaryAdJustment->amount
        ],
        false
      );
    }

    return $this->ok();
  }

  public function destroy(
    Institution $institution,
    PayrollAdjustmentType $payrollAdjustmentType
  ) {
    if (
      $payrollAdjustmentType->payrollAdjustments->isNotEmpty() ||
      $payrollAdjustmentType->children->isNotEmpty()
    ) {
      return $this->message(
        'This record can not be deleted because it is associated with some Salary Adjustments or other Adjustment Types.',
        403
      );
    }

    $payrollAdjustmentType->delete();
    return $this->ok();
  }
}
