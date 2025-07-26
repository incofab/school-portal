<?php

use App\Enums\YearMonth;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\PayrollAdjustmentType;
use App\Models\PayrollSummary;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->payrollSummary = PayrollSummary::factory()
    ->notEvaluated()
    ->for($this->institution)
    ->create();
  actingAs($this->instAdmin);
});

it('lists all payroll adjustments for an institution', function () {
  PayrollAdjustment::factory()
    ->for($this->institution)
    ->for($this->payrollSummary)
    ->count(2)
    ->create();

  getJson(
    route('institutions.payroll-summaries.payroll-adjustments.index', [
      $this->institution->uuid,
      $this->payrollSummary
    ])
  )
    ->assertOk()
    ->assertSee('payrollAdjustments');
});

it('lists adjustments for a specific payroll', function () {
  $payroll = Payroll::factory()
    ->institutionUser($this->institutionUser)
    ->create();

  PayrollAdjustment::factory()
    ->institutionUser($this->institutionUser)
    ->count(2)
    ->create();
  getJson(
    route('institutions.payroll-adjustments.payroll', [
      $this->institution->uuid,
      $payroll->id
    ])
  )
    ->assertOk()
    ->assertSee('payrollAdjustments');
});

it('stores a new payroll adjustment', function () {
  $adjustmentType = PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->create();

  $ref = Str::uuid();

  $payload = [
    'payroll_adjustment_type_id' => $adjustmentType->id,
    'description' => 'Test Bonus',
    'amount' => 1000,
    'month' => YearMonth::January->value,
    'year' => now()->year,
    'reference' => $ref,
    'institution_user_ids' => [$this->institutionUser->id]
  ];

  postJson(
    route('institutions.payroll-summaries.payroll-adjustments.store', [
      $this->institution->uuid,
      $this->payrollSummary
    ]),
    $payload
  )->assertOk();

  $this->assertDatabaseHas('payroll_adjustments', [
    'reference' => $ref,
    'institution_id' => $this->institution->id,
    'institution_user_id' => $this->institutionUser->id
  ]);
});

it('updates an existing payroll adjustment', function () {
  $adjustmentType = PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->create();

  $adjustment = PayrollAdjustment::factory()
    ->institutionUser($this->institutionUser)
    ->for($this->payrollSummary)
    ->create([
      'payroll_adjustment_type_id' => $adjustmentType->id,
      'amount' => 200
    ]);

  $data = [
    'description' => 'Updated Adjustment',
    'amount' => 500,
    'institution_user_id' => $this->institutionUser->id
  ];
  putJson(
    route('institutions.payroll-adjustments.update', [
      $this->institution->uuid,
      $adjustment->id
    ]),
    $data
  )->assertOk();

  $this->assertDatabaseHas('payroll_adjustments', [
    'id' => $adjustment->id,
    'amount' => 500,
    'description' => 'Updated Adjustment'
  ]);
});

it('prevents updating an evaluated payroll adjustment', function () {
  $adjustmentType = PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->create();
  $summary = PayrollSummary::factory()
    ->evaluated()
    ->for($this->institution)
    ->create();
  $adjustment = PayrollAdjustment::factory()
    ->for($this->institution)
    ->for($summary)
    ->create([
      'payroll_adjustment_type_id' => $adjustmentType->id,
      'institution_user_id' => $this->institutionUser->id
    ]);

  $data = [
    'description' => 'Illegal update',
    'amount' => 999,
    'institution_user_id' => $this->institutionUser->id
  ];

  putJson(
    route('institutions.payroll-adjustments.update', [
      $this->institution->uuid,
      $adjustment->id
    ]),
    $data
  )
    ->assertForbidden()
    ->assertSee('already been evaluated');
});

it('deletes a payroll adjustment if not evaluated', function () {
  $adjustment = PayrollAdjustment::factory()
    ->for($this->institution)
    ->for($this->payrollSummary)
    ->create();

  deleteJson(
    route('institutions.payroll-adjustments.destroy', [
      $this->institution->uuid,
      $adjustment->id
    ])
  )->assertOk();

  $this->assertSoftDeleted($adjustment);
});

it('prevents deletion of evaluated payroll adjustment', function () {
  $summary = PayrollSummary::factory()
    ->for($this->institution)
    ->evaluated()
    ->create();

  $adjustment = PayrollAdjustment::factory()
    ->for($this->institution)
    ->for($summary)
    ->create();

  deleteJson(
    route('institutions.payroll-adjustments.destroy', [
      $this->institution->uuid,
      $adjustment->id
    ])
  )
    ->assertForbidden()
    ->assertSee('already been evaluated');
});
