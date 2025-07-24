<?php

use App\Models\{
  Institution,
  InstitutionUser,
  PayrollAdjustment,
  PayrollAdjustmentType,
  PayrollSummary,
  Salary,
  SalaryType
};
use App\Enums\TransactionType;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;

  $this->staff = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  $this->salaryTypeCredit = SalaryType::factory()
    ->for($this->institution)
    ->credit()
    ->create(['title' => 'Base Salary']);

  $this->salaryTypeDebit = SalaryType::factory()
    ->for($this->institution)
    ->debit()
    ->create(['title' => 'Tax']);

  $this->payrollAdjustmentTypeCredit = PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->credit()
    ->create(['title' => 'Bonus']);

  $this->payrollAdjustmentTypeDebit = PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->debit()
    ->create(['title' => 'Leave Deduction']);

  $this->payrollSummary = PayrollSummary::factory()
    ->for($this->institution)
    ->notEvaluated()
    ->create();

  // Create salary records
  Salary::factory()
    ->institutionUser($this->staff)
    ->for($this->salaryTypeCredit)
    ->create(['amount' => 50000]);

  Salary::factory()
    ->institutionUser($this->staff)
    ->for($this->salaryTypeDebit)
    ->create(['amount' => 5000]);

  // Create payroll adjustments
  PayrollAdjustment::factory()
    ->institutionUser($this->staff)
    ->for($this->payrollSummary)
    ->for($this->payrollAdjustmentTypeCredit)
    ->create(['amount' => 2000]);

  PayrollAdjustment::factory()
    ->institutionUser($this->staff)
    ->for($this->payrollSummary)
    ->for($this->payrollAdjustmentTypeDebit)
    ->create(['amount' => 1000]);

  actingAs($this->instAdmin);
});

it('will not work if payroll summary has already been evaluated', function () {
  $payrollSummary = PayrollSummary::factory()
    ->for($this->institution)
    ->evaluated()
    ->create();
  postJson(
    route('institutions.payroll-summaries.generate-payroll', [
      $this->institution->uuid,
      $payrollSummary->id
    ])
  )->assertForbidden();
});

it('generates payroll for all staff and updates summary', function () {
  postJson(
    route('institutions.payroll-summaries.generate-payroll', [
      $this->institution->uuid,
      $this->payrollSummary->id
    ])
  )->assertOk();

  $this->payrollSummary->refresh();
  expect($this->payrollSummary->evaluated_at)->not->toBeNull();
  expect($this->payrollSummary->amount)->toBe(46000.0); // 50000 - 5000 + 2000 - 1000
});
