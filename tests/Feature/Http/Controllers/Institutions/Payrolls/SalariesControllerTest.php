<?php

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Salary;
use App\Models\SalaryType;
use function Pest\Laravel\{
  actingAs,
  assertModelMissing,
  get,
  postJson,
  putJson,
  deleteJson
};

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

it('displays salaries index page', function () {
  $salary = Salary::factory()
    ->for($this->institution)
    ->for($this->institutionUser)
    ->create(['salary_type_id' => $this->salaryType->id]);

  get(route('institutions.salaries.index', $this->institution->uuid))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/payrolls/list-salaries')
        ->has('salaries.data')
        ->has('salaryTypes')
    );
});

it('can store a salary', function () {
  $data = [
    'salary_type_id' => $this->salaryType->id,
    'description' => 'Monthly salary',
    'amount' => 50000,
    'institution_user_id' => $this->institutionUser->id
  ];

  postJson(
    route('institutions.salaries.store', $this->institution->uuid),
    $data
  )->assertOk();

  $this->assertDatabaseHas('salaries', [
    'institution_id' => $this->institution->id,
    'salary_type_id' => $data['salary_type_id'],
    'institution_user_id' => $data['institution_user_id'],
    'amount' => $data['amount']
  ]);
});

it('can update a salary', function () {
  $salary = Salary::factory()
    ->for($this->institution)
    ->for($this->institutionUser)
    ->create(['salary_type_id' => $this->salaryType->id, 'amount' => 10000]);

  $newData = [
    'description' => 'Updated salary',
    'amount' => 55000,
    'institution_user_id' => $this->institutionUser->id,
    'salary_type_id' => $this->salaryType->id
  ];

  putJson(
    route('institutions.salaries.update', [
      $this->institution->uuid,
      $salary->id
    ]),
    $newData
  )->assertOk();

  $this->assertDatabaseHas('salaries', [
    'id' => $salary->id,
    'description' => 'Updated salary',
    'amount' => 55000
  ]);
});

it('can delete a salary', function () {
  $salary = Salary::factory()
    ->for($this->institution)
    ->for($this->institutionUser)
    ->create(['salary_type_id' => $this->salaryType->id]);

  deleteJson(
    route('institutions.salaries.destroy', [
      $this->institution->uuid,
      $salary->id
    ])
  )->assertOk();

  assertModelMissing($salary);
});
