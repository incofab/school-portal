<?php
namespace App\Actions\Payrolls;

use App\Enums\TransactionType;
use App\Models\Institution;
use App\Models\Payroll;
use App\Models\PayrollSummary;
use DB;

class GeneratePayroll
{
  function __construct(
    private Institution $institution,
    private PayrollSummary $payrollSummary
  ) {
  }

  function run()
  {
    $allInstitutionStaff = $this->institution->staff;

    $summaryAmount = 0;
    $summaryTotalDeductions = 0;
    $summaryTotalBonuses = 0;

    DB::beginTransaction();
    foreach ($allInstitutionStaff as $staff) {
      $salaries = $staff
        ->salaries()
        ->with(['salaryType.parent'])
        ->get();
      $payrollAdjustments = $staff
        ->payrollAdjustments()
        ->where('payroll_summary_id', $this->payrollSummary->id)
        ->with(['payrollAdjustmentType'])
        ->get();

      [$salary, $salaryDeduction, $salBreakdown] = $this->calcSalaries(
        $salaries
      );
      [$bonuses, $deductions, $adjBreakdown] = $this->calcAdjustments(
        $payrollAdjustments
      );

      $totalDeductions = $salaryDeduction + $deductions;
      $netSalary = $salary + $bonuses - $totalDeductions;

      Payroll::query()->updateOrCreate(
        [
          'institution_user_id' => $staff->id,
          'institution_id' => $this->institution->id,
          'payroll_summary_id' => $this->payrollSummary->id
        ],
        [
          'gross_salary' => $salary + $bonuses,
          'total_deductions' => $totalDeductions,
          'total_bonuses' => $bonuses,
          'net_salary' => $netSalary,
          'meta' => [
            'salaries' => $salBreakdown,
            'adjustments' => $adjBreakdown
          ]
        ]
      );

      $summaryAmount += $netSalary;
      $summaryTotalDeductions += $totalDeductions;
      $summaryTotalBonuses += $bonuses;
    }

    $this->payrollSummary
      ->fill([
        'amount' => $summaryAmount,
        'total_deduction' => $summaryTotalDeductions,
        'total_bonuses' => $summaryTotalBonuses,
        'evaluated_at' => now()
      ])
      ->save();
    DB::commit();
  }

  private function calcSalaries($salaries)
  {
    $income = 0;
    $deductions = 0;
    $breakdown = [];
    foreach ($salaries as $salary) {
      $type = $salary->salaryType->type;
      $amount = $salary->amount;
      if ($type === TransactionType::Credit) {
        $income += $amount;
      } else {
        $deductions += $amount;
      }
      $breakdown[] = [
        'type' => $type,
        'amount' => $amount,
        'title' => $salary->salaryType->title
      ];
    }
    return [$income, $deductions, $breakdown];
  }

  private function calcAdjustments($payrollAdjustments)
  {
    $bonuses = 0;
    $deductions = 0;
    $breakdown = [];
    foreach ($payrollAdjustments as $payrollAdjustment) {
      $type = $payrollAdjustment->payrollAdjustmentType->type;
      $amount = $payrollAdjustment->amount;
      if ($type === TransactionType::Credit) {
        $bonuses += $amount;
      } else {
        $deductions += $amount;
      }
      $breakdown[] = [
        'type' => $type,
        'amount' => $amount,
        'title' => $payrollAdjustment->payrollAdjustmentType->title
      ];
    }

    return [$bonuses, $deductions, $breakdown];
  }
}
