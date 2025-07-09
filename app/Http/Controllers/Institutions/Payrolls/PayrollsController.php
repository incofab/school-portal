<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Models\Institution;
use Illuminate\Http\Request;
use App\Enums\InstitutionUserType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\PayrollRequest;
use App\Http\Requests\SalaryAdjustmentRequest;
use App\Models\Payroll;
use App\Models\PayrollSummary;
use App\Models\SalaryAdjustment;
use Illuminate\Auth\Events\Validated;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use JmesPath\Env;

class PayrollsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  /*
  public function index(Institution $institution)
  {
    $query = $institution->payrolls()->with(['payrollSummary', 'institutionUser.user'])->latest('id');

    return inertia('institutions/payrolls/list-payrolls', [
      'payrolls' => paginateFromRequest($query)
    ]);
  }
  */

  //= Generate "Payroll" and "Payroll Summary" based on the supplied month and year
  public function generatePayroll(Institution $institution, PayrollRequest $request)
  {
    $validatedData = $request->validated();

    $month = $validatedData['month'];
    $year = $validatedData['year'];

    $getPayroll = $institution->payrollSummaries()->where('month', $month)->where('year', $year)->first();

    //=
    if ($getPayroll) {
      return $this->message(
        "A Payroll record already exist for the selected Month.",
        403
      );
    }

    $generatedPayrolls = []; //An array that will hold all the generated payroll records before saving them to the database - once.

    $institutionStaff = $institution->staff; //returns InstitutionUser records

    foreach ($institutionStaff as $staff) {
      $staffSalaries = $staff->staffSalaries()->with(['salaryType.parent'])->get();
      $staffSalaryAdjustments = $staff->salaryAdjustments()
        ->where('month', $month)
        ->where('year', $year)
        ->with(['adjustmentType'])
        ->get();

      $calcSalary = $this->calcSalaries($staff, $staffSalaries);
      $calcAdjustments = $this->calcAdjustments($staff, $staffSalaryAdjustments, $month, $year);

      $income = $calcSalary[0];
      $bonuses = $calcAdjustments[0];
      $deductions = $calcSalary[1] + $calcAdjustments[1];

      $netIncome = ($income + $bonuses) - $deductions;


      $generatedPayrolls[] = [
        'institution_id' => $institution->id,
        'institution_user_id' => $staff->id,
        'net_amount' => $netIncome,
        'total_deductions' => $deductions,
        'total_bonuses' => $bonuses,
        'income' => $income,
      ];
    }

    //= Now, create a PayrollSummary record.
    $amount = 0;
    $totalDeductions = 0;
    $totalBonuses = 0;

    foreach ($generatedPayrolls as $generatedPayroll) {
      $amount += ($generatedPayroll['net_amount'] + $generatedPayroll['total_bonuses']);
      $totalDeductions += $generatedPayroll['total_deductions'];
      $totalBonuses += $generatedPayroll['total_bonuses'];
    };

    // $payrollSummary = PayrollSummary::create([
    $payrollSummary = $institution->payrollSummaries()->create([
      'amount' => $amount,
      'total_deduction' => $totalDeductions,
      'total_bonuses' => $totalBonuses,
      'month' => $month,
      'year' => $year,
    ]);

    //= Add payroll_summary_id to each payroll record
    $payrollsWithSummaryId = array_map(function ($payroll) use ($payrollSummary) {
      $payroll['payroll_summary_id'] = $payrollSummary->id;
      return $payroll;
    }, $generatedPayrolls);

    // Bulk insert payroll records
    Payroll::insert($payrollsWithSummaryId);

    return $this->ok();
  }

  private function calcSalaries($staff, $staffSalaries)
  {
    $income = 0;
    $deductions = 0;

    foreach ($staffSalaries as $staffSalary) {
      $type = $staffSalary->salaryType->type;

      /*
      //= Calc Amount.
      $parentId = $staffSalary->salaryType->parent_id;
      if ($parentId) {
        $percentage = $staffSalary->salaryType->percentage;

        $parent = $staff->staffSalaries()->where('salary_type_id', $parentId)->first();
        $parentAmount = $parent->amount;
        $amount = ($percentage / 100) * $parentAmount;
      } else {
        $amount = $staffSalary->amount;
      }
      */

      $amount = $staffSalary->actual_amount;

      //= Income OR Deduction
      if ($type === TransactionType::Credit->value) {
        $income += $amount;
      } else {
        $deductions += $amount;
      }
    }

    return [$income, $deductions];
  }

  private function calcAdjustments($staff, $staffSalaryAdjustments, $month, $year)
  {
    $bonuses = 0;
    $deductions = 0;

    foreach ($staffSalaryAdjustments as $salaryAdjustment) {
      $type = $salaryAdjustment->adjustmentType->type;

      /*
      //= Calc Amount.
      $parentId = $salaryAdjustment->adjustmentType->parent_id;
      if ($parentId) {
        $percentage = $salaryAdjustment->adjustmentType->percentage;

        $parent = $staff->salaryAdjustments()
          ->where('adjustment_type_id', $parentId)
          ->where('month', $month)
          ->where('year', $year)
          ->first();

        $parentAmount = $parent->amount;
        $amount = ($percentage / 100) * $parentAmount;
      } else {
        $amount = $salaryAdjustment->amount;
      }
      */

      $amount = $salaryAdjustment->actual_amount;

      //= Income OR Deduction
      if ($type === TransactionType::Credit->value) {
        $bonuses += $amount;
      } else {
        $deductions += $amount;
      }
    }

    return [$bonuses, $deductions];
  }
}
