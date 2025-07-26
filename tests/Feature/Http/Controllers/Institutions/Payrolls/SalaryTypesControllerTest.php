<?php

use App\Models\Institution;
use App\Models\SalaryType;
use App\Enums\TransactionType;
use App\Models\InstitutionUser;
use App\Models\Salary;

use function Pest\Laravel\{
  actingAs,
  assertDatabaseHas,
  assertModelMissing,
  getJson,
  deleteJson,
  postJson,
  putJson
};

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->salaryType = SalaryType::factory()
    ->for($this->institution)
    ->create();
  actingAs($this->instAdmin);
});

it('can list salary types', function () {
  getJson(route('institutions.salary-types.index', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/payrolls/list-salary-types')
        ->has('salaryTypes.data')
        ->has('salaryTypesArray')
    );
});

it('can store a new salary type', function () {
  $data = [
    'title' => 'Housing Allowance',
    'type' => TransactionType::Credit->value,
    'percentage' => 10
  ];

  postJson(
    route('institutions.salary-types.store', $this->institution),
    $data
  )->assertOk();

  expect(
    $this->institution
      ->salaryTypes()
      ->where('title', 'Housing Allowance')
      ->exists()
  )->toBeTrue();
});

it('prevents storing duplicate salary type', function () {
  $existing = SalaryType::factory()
    ->for($this->institution)
    ->create([
      'title' => 'Transport',
      'type' => TransactionType::Credit->value
    ]);

  postJson(route('institutions.salary-types.store', $this->institution), [
    'title' => $existing->title,
    'type' => $existing->type
  ])
    ->assertForbidden()
    ->assertSee('A similar record already exist.');
});

it('can update a salary type', function () {
  $newTitle = 'Updated Salary';
  putJson(
    route('institutions.salary-types.update', [
      $this->institution,
      $this->salaryType
    ]),
    [
      'title' => $newTitle,
      'type' => $this->salaryType->type
    ]
  )->assertOk();

  $this->assertDatabaseHas('salary_types', [
    'id' => $this->salaryType->id,
    'title' => $newTitle
  ]);
});

it('can delete a salary type without dependencies', function () {
  $deletable = SalaryType::factory()
    ->for($this->institution)
    ->create();

  deleteJson(
    route('institutions.salary-types.destroy', [$this->institution, $deletable])
  )->assertOk();

  expect(SalaryType::find($deletable->id))->toBeNull();
});

it('prevents deleting salary type with salaries or children', function () {
  [$salaryType, $salaryType2] = SalaryType::factory(2)
    ->for($this->institution)
    ->create();
  $institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();
  Salary::factory(2)
    ->institutionUser($institutionUser)
    ->for($salaryType)
    ->create();

  deleteJson(
    route('institutions.salary-types.destroy', [
      $this->institution,
      $salaryType
    ])
  )
    ->assertForbidden()
    ->assertSee('This record can not be deleted');

  deleteJson(
    route('institutions.salary-types.destroy', [
      $this->institution,
      $salaryType2
    ])
  )->assertOk();
  assertDatabaseHas('salary_types', [
    'id' => $salaryType->id
  ]);
  assertModelMissing($salaryType2);
});
