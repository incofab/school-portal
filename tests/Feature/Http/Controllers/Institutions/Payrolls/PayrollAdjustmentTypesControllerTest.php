<?php

use App\Enums\TransactionType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\PayrollAdjustment;
use App\Models\PayrollAdjustmentType;
use App\Models\PayrollSummary;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->admin()
    ->create();

  actingAs($this->instAdmin);
});

it('can list payroll adjustment types for an institution', function () {
  PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->count(2)
    ->create();

  getJson(
    route(
      'institutions.payroll-adjustment-types.index',
      $this->institution->uuid
    )
  )
    ->assertOk()
    ->assertSee('payrollAdjustmentTypes');
});

it('can store a new payroll adjustment type', function () {
  $data = [
    'title' => 'Medical Bonus',
    'type' => TransactionType::Credit->value,
    'description' => 'A bonus for medical personnel'
  ];

  postJson(
    route(
      'institutions.payroll-adjustment-types.store',
      $this->institution->uuid
    ),
    $data
  )->assertOk();

  $this->assertDatabaseHas('payroll_adjustment_types', [
    'title' => 'Medical Bonus',
    'institution_id' => $this->institution->id
  ]);
});

it('prevents duplicate payroll adjustment types', function () {
  PayrollAdjustmentType::factory()->create([
    'institution_id' => $this->institution->id,
    'title' => 'Duplicate Bonus',
    'type' => TransactionType::Debit
  ]);

  $data = [
    'title' => 'Duplicate Bonus',
    'type' => TransactionType::Debit->value
  ];

  postJson(
    route(
      'institutions.payroll-adjustment-types.store',
      $this->institution->uuid
    ),
    $data
  )->assertJsonValidationErrorFor('title');
});

it('can update a payroll adjustment type and related adjustments', function () {
  $type = PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->create([
      'title' => 'Leave Deduction',
      'type' => TransactionType::Debit
    ]);
  $summary = PayrollSummary::factory()->create();
  $adjustment = PayrollAdjustment::factory()
    ->for($this->institution)
    ->create([
      'payroll_adjustment_type_id' => $type->id,
      'payroll_summary_id' => $summary->id,
      'institution_user_id' => $this->institutionUser->id,
      'amount' => 200
    ]);

  $payload = [
    'title' => 'Updated Leave Deduction',
    'type' => TransactionType::Debit->value
  ];

  putJson(
    route('institutions.payroll-adjustment-types.update', [
      $this->institution->uuid,
      $type->id
    ]),
    $payload
  )->assertOk();

  $this->assertDatabaseHas('payroll_adjustment_types', [
    'id' => $type->id,
    'title' => 'Updated Leave Deduction'
  ]);
});

it('can delete a payroll adjustment type without dependencies', function () {
  $type = PayrollAdjustmentType::factory()
    ->for($this->institution)
    ->create();

  deleteJson(
    route('institutions.payroll-adjustment-types.destroy', [
      $this->institution->uuid,
      $type->id
    ])
  )->assertOk();

  assertSoftDeleted('payroll_adjustment_types', ['id' => $type->id]);
});

it(
  'prevents deleting adjustment type with payroll adjustments or children',
  function () {
    $type = PayrollAdjustmentType::factory()
      ->for($this->institution)
      ->create();
    PayrollAdjustment::factory()
      ->for($this->institution)
      ->create([
        'payroll_adjustment_type_id' => $type->id
      ]);

    deleteJson(
      route('institutions.payroll-adjustment-types.destroy', [
        $this->institution->uuid,
        $type->id
      ])
    )
      ->assertForbidden()
      ->assertSee('can not be deleted');
  }
);
