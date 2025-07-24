<?php

namespace App\Http\Controllers\Institutions\Payrolls;

use App\Actions\Payrolls\GeneratePayroll;
use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\PayrollSummary;

class PayrollsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ]);
  }

  public function generatePayroll(
    Institution $institution,
    PayrollSummary $payrollSummary
  ) {
    abort_if($payrollSummary->isEvaluated(), 403, 'Payroll already evaluated');
    (new GeneratePayroll($institution, $payrollSummary))->run();
    return $this->ok();
  }
}
