<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Actions\Payrolls\PayrollAdjustmentHandler;
use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollAdjustmentRequest;
use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\PayrollAdjustmentType;
use App\Models\PayrollSummary;

class PayrollAdjustmentsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  //= Grab the PayrollAdjustments associated with a given Payroll.
  public function payrollAdjustments(Institution $institution, Payroll $payroll)
  {
    $query = PayrollAdjustment::where(
      'institution_user_id',
      $payroll->institution_user_id
    )
      ->where('payroll_summary_id', $payroll->payroll_summary_id)
      ->with('payrollAdjustmentType', 'institutionUser.user');

    return inertia(
      'institutions/payroll-adjustments/list-payroll-adjustments',
      [
        'payrollAdjustments' => paginateFromRequest($query),
        'payrollAdjustmentTypes' => $institution->payrollAdjustmentTypes,
        'parentAdjustmentTypes' => $institution->parentAdjustmentTypes
      ]
    );
  }

  public function index(
    Institution $institution,
    PayrollSummary $payrollSummary
  ) {
    $query = $payrollSummary
      ->payrollAdjustments()
      ->with(
        'payrollAdjustmentType.parent',
        'institutionUser.user',
        'payrollSummary'
      )
      ->latest('id');

    return inertia('institutions/payrolls/list-payroll-adjustments', [
      'payrollAdjustments' => paginateFromRequest($query),
      'payrollAdjustmentTypes' => $institution->payrollAdjustmentTypes()->get(),
      'parentAdjustmentTypes' => $institution->parentAdjustmentTypes()->get()
    ]);
  }

  public function store(
    Institution $institution,
    PayrollAdjustmentRequest $request
  ) {
    $validatedData = $request->validated();
    $adjustmentType = PayrollAdjustmentType::query()
      ->with('parent')
      ->findOrFail($validatedData['payroll_adjustment_type_id']);

    (new PayrollAdjustmentHandler($institution))->createMultiple(
      $adjustmentType,
      $validatedData
    );
    return $this->ok();
  }

  public function update(
    Institution $institution,
    PayrollAdjustment $payrollAdjustment,
    PayrollAdjustmentRequest $request
  ) {
    $payrollAdjustment->load('payrollSummary', 'payrollAdjustmentType');
    $validatedData = $request->validated();

    abort_if(
      $payrollAdjustment->payrollSummary->isEvaluated(),
      403,
      'Record can NOT be modified because the payroll has already been evaluated.'
    );

    (new PayrollAdjustmentHandler($institution))->update(
      $payrollAdjustment,
      $validatedData
    );
    return $this->ok();
  }

  public function destroy(
    Institution $institution,
    PayrollAdjustment $payrollAdjustment
  ) {
    abort_if(
      $payrollAdjustment->payrollSummary->isEvaluated(),
      403,
      'Record can NOT be deleted because the Payroll has already been evaluated.'
    );

    $payrollAdjustment->delete();
    return $this->ok();
  }
}
