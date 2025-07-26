<?php
namespace App\Actions\Payrolls;

use App\Models\Institution;
use App\Models\PayrollSummary;
use App\Models\PayrollAdjustment;
use App\Models\PayrollAdjustmentType;

class PayrollAdjustmentHandler
{
  function __construct(private Institution $institution)
  {
  }

  /**
   * @param array {
   *  payroll_adjustment_type_id: int,
   *  title: string,
   *  description: string,
   *  amount?: float,
   *  institution_user_id: int,
   *  reference: string
   * } $data
   */
  function create(
    PayrollSummary $payrollSummary,
    PayrollAdjustmentType $adjustmentType,
    $data
  ) {
    $suppliedAmount = $data['amount'] ?? 0;
    $amount = $this->getAmount(
      $adjustmentType,
      $data['institution_user_id'],
      $suppliedAmount
    );

    $this->institution->payrollAdjustments()->firstOrCreate(
      [
        'institution_user_id' => $data['institution_user_id'],
        'reference' => $data['reference']
      ],
      [
        ...collect($data)
          ->except('year', 'month')
          ->toArray(),
        'amount' => $amount,
        'payroll_summary_id' => $payrollSummary->id
      ]
    );
  }

  /**
   * @param array {
   *  payroll_adjustment_type_id: int,
   *  title: string,
   *  description: string,
   *  amount?: float,
   *  institution_user_ids: int[],
   *  reference: string
   * } $data
   */
  function createMultiple(
    PayrollSummary $payrollSummary,
    PayrollAdjustmentType $payrollAdjustmentType,
    $data
  ) {
    foreach ($data['institution_user_ids'] as $key => $institutionUserId) {
      $data['institution_user_id'] = $institutionUserId;
      $this->create($payrollSummary, $payrollAdjustmentType, [
        ...collect($data)
          ->except('institution_user_ids')
          ->toArray(),
        'institution_user_id' => $institutionUserId
      ]);
    }
  }

  /**
   * @param array {
   *  title: string,
   *  description?: string,
   *  amount?: float,
   * } $data
   */
  function update(PayrollAdjustment $payrollAdjustment, $data, $canAbort = true)
  {
    $adjustmentType = $payrollAdjustment->payrollAdjustmentType;

    $suppliedAmount = $data['amount'] ?? 0;
    $amount = $this->getAmount(
      $adjustmentType,
      $payrollAdjustment->institution_user_id,
      $suppliedAmount,
      $canAbort
    );

    $payrollAdjustment
      ->fill([
        'description' =>
          $data['description'] ?? $payrollAdjustment->description,
        'amount' => $amount
      ])
      ->save();
  }

  function getAmount(
    PayrollAdjustmentType $adjustmentType,
    int $institutionUserId,
    $suppliedAmount = 0,
    $canAbort = true
  ) {
    $parentAdjustmentType = $adjustmentType->parent;
    if (!$parentAdjustmentType) {
      return $suppliedAmount;
    }

    $parentPayrollAdjustment = $parentAdjustmentType
      ->payrollAdjustments()
      ->where('institution_user_id', $institutionUserId)
      ->first();
    if (!$parentPayrollAdjustment) {
      abort_if(
        $canAbort,
        401,
        "No entry recorded for the parent ({$parentAdjustmentType->title}) for this user"
      );
      return $suppliedAmount;
    }
    return ($adjustmentType->percentage / 100) *
      $parentPayrollAdjustment->amount;
  }
}
