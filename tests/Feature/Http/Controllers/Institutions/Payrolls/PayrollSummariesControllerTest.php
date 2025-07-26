<?php

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Payroll;
use App\Models\PayrollSummary;
use App\Models\SalaryType;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;

  $this->salaryType = SalaryType::factory()
    ->for($this->institution)
    ->create();

  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  actingAs($this->instAdmin);
});

it('can list payroll summaries for an institution', function () {
  PayrollSummary::factory()
    ->count(2)
    ->for($this->institution)
    ->create();

  getJson(
    route('institutions.payroll-summaries.index', $this->institution->uuid)
  )
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/payrolls/list-payroll-summaries')
        ->has('payrollSummaries.data')
    );
});

it('can show a specific payroll summary and its payrolls', function () {
  $payrollSummary = PayrollSummary::factory()
    ->for($this->institution)
    ->create();

  Payroll::factory(3)
    ->for($payrollSummary)
    ->institutionUser($this->institutionUser)
    ->create();

  getJson(
    route('institutions.payroll-summaries.show', [
      $this->institution->uuid,
      $payrollSummary->id
    ])
  )
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/payrolls/list-payrolls')
        ->has('payrolls.data')
        ->where('payrollSummary.id', $payrollSummary->id)
    );
});
