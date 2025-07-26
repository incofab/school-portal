<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Payroll;

class PayrollsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ]);
  }

  function show(Institution $institution, Payroll $payroll)
  {
    $payroll->load('institutionUser.user', 'payrollSummary');
    return inertia('institutions/payrolls/show-payroll', [
      'payroll' => $payroll
    ]);
  }
}
