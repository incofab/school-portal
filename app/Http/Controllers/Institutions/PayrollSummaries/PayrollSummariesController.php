<?php

namespace App\Http\Controllers\Institutions\PayrollSummaries;

use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\PayrollSummary;

class PayrollSummariesController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function index(Institution $institution)
  {
    $query = $institution->payrollSummaries()->latest('id');

    return inertia('institutions/payroll-summaries/list-payroll-summaries', [
      'payrollSummaries' => paginateFromRequest($query)
    ]);
  }

  public function show(Institution $institution, PayrollSummary $payrollSummary)
  {
    $query = $payrollSummary->payrolls()->with(['payrollSummary', 'institutionUser.user'])->latest('id');

    return inertia('institutions/payrolls/list-payrolls', [
      'payrolls' => paginateFromRequest($query),
      'payrollSummary' => $payrollSummary
    ]);
  }
}
