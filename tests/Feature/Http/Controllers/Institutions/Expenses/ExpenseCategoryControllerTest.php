<?php

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\ExpenseCategory;
use Illuminate\Testing\Fluent\AssertableJson;
use function Pest\Laravel\{getJson, postJson, putJson, deleteJson, actingAs};

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;

  // Create a user attached to the institution (non-admin)
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->teacher()
    ->create();

  actingAs($this->instAdmin);
});

it('can list expense categories for institution', function () {
  ExpenseCategory::factory()
    ->for($this->institution)
    ->count(2)
    ->create();

  getJson(
    route('institutions.expense-categories.index', $this->institution->uuid)
  )
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/expenses/list-expense-categories')
        ->has('expenseCategories.data')
    );
});

it('can store a new expense category', function () {
  $payload = [
    'title' => 'Stationery',
    'description' => 'Books, pens, etc.'
  ];

  postJson(
    route('institutions.expense-categories.store', $this->institution->uuid),
    $payload
  )->assertOk();

  $this->assertDatabaseHas('expense_categories', [
    'institution_id' => $this->institution->id,
    'title' => 'Stationery'
  ]);
});

it('can update an existing expense category', function () {
  $category = ExpenseCategory::factory()
    ->for($this->institution)
    ->create(['title' => 'Old Title']);

  $payload = ['title' => 'Updated Title'];

  putJson(
    route('institutions.expense-categories.update', [
      $this->institution->uuid,
      $category->id
    ]),
    $payload
  )->assertOk();

  $this->assertDatabaseHas('expense_categories', [
    'id' => $category->id,
    'title' => 'Updated Title'
  ]);
});

it('can delete an expense category', function () {
  $category = ExpenseCategory::factory()
    ->for($this->institution)
    ->create();

  deleteJson(
    route('institutions.expense-categories.destroy', [
      $this->institution->uuid,
      $category->id
    ])
  )->assertOk();

  $this->assertSoftDeleted($category);
});
