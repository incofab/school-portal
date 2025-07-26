<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Actions\GenericExport;
use App\Actions\Payrolls\GeneratePayroll;
use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Enums\YearMonth;
use App\Http\Controllers\Controller;
use App\Models\PayrollSummary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class PayrollSummariesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ]);
  }

  public function index(Institution $institution)
  {
    $query = $institution
      ->payrollSummaries()
      ->withCount('payrolls')
      ->latest('id');

    return inertia('institutions/payrolls/list-payroll-summaries', [
      'payrollSummaries' => paginateFromRequest($query)
    ]);
  }

  public function show(Institution $institution, PayrollSummary $payrollSummary)
  {
    $query = $payrollSummary
      ->payrolls()
      ->with(['payrollSummary', 'institutionUser.user'])
      ->latest('id');

    return inertia('institutions/payrolls/list-payrolls', [
      'payrolls' => paginateFromRequest($query),
      'payrollSummary' => $payrollSummary
    ]);
  }

  public function store(Institution $institution, Request $request)
  {
    $currentYear = date('Y');
    $data = $request->validate([
      'month' => ['required', new Enum(YearMonth::class)],
      'year' => ['required', 'integer', 'min:' . $currentYear - 1]
    ]);

    return PayrollSummary::query()->firstOrCreate([
      'institution_id' => $institution->id,
      ...$data
    ]);
    return $this->ok();
  }

  public function generatePayroll(
    Institution $institution,
    PayrollSummary $payrollSummary,
    Request $request
  ) {
    $request->validate(['re_evaluate' => ['nullable', 'boolean']]);
    abort_if(
      $payrollSummary->isEvaluated() && !$request->re_evaluate,
      403,
      'Payroll already evaluated'
    );
    (new GeneratePayroll($institution, $payrollSummary))->run();
    return $this->ok();
  }

  function downloadPayrollSummary(
    Institution $institution,
    PayrollSummary $payrollSummary
  ) {
    $filename = "payroll-{$payrollSummary->month}-{$payrollSummary->year}.xlsx";
    $payrolls = $payrollSummary
      ->payrolls()
      ->with('institutionUser.user')
      ->get();
    $data = $payrolls->map(
      fn($item) => [
        'Name' => $item->institutionUser->user->full_name,
        'Total Deductions' => number_format($item->total_deductions, 1),
        'Total Bonuses' => number_format($item->total_bonuses, 1),
        'Gross Salary' => number_format($item->gross_salary, 1),
        'Net Salary' => number_format($item->net_salary, 1)
      ]
    );
    return (new GenericExport($data, $filename))->download();
  }
}
