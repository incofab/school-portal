<?php

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\ExpenseCategory;
use App\Models\AcademicSession;
use App\Models\Expense;
use Illuminate\Support\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\deleteJson;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;

  $this->expenseCategory = ExpenseCategory::factory()
    ->for($this->institution)
    ->create();

  $this->academicSession = AcademicSession::factory()->create();

  $this->institutionUser = InstitutionUser::factory()
    ->for($this->institution)
    ->admin()
    ->create();

  actingAs($this->instAdmin);
});

it('can view the expense list page', function () {
  Expense::factory()
    ->createdBy($this->institutionUser)
    ->for($this->academicSession)
    ->create(['amount' => 500]);

  getJson(route('institutions.expenses.index', $this->institution->uuid))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/expenses/list-expenses')
        ->has('expenses.data')
        ->has('expense_total')
        ->has('expense_count')
    );
});

it('can view the create expense page', function () {
  getJson(route('institutions.expenses.create', $this->institution->uuid))
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/expenses/create-edit-expense')
        ->has('expenseCategories')
    );
});

it('can store a new expense', function () {
  $data = [
    'title' => 'New Projector',
    'description' => 'Bought a new projector',
    'amount' => 12000.5,
    'academic_session_id' => $this->academicSession->id,
    'term' => 'first',
    'expense_date' => Carbon::today()->toDateString(),
    'expense_category_id' => $this->expenseCategory->id
  ];

  postJson(
    route('institutions.expenses.store', $this->institution->uuid),
    $data
  )->assertOk();

  $this->assertDatabaseHas('expenses', [
    'title' => 'New Projector',
    'amount' => 12000.5,
    'created_by' => currentInstitutionUser()->id,
    'institution_id' => $this->institution->id
  ]);
});

it('validates input when storing expense', function () {
  $invalidData = [
    'title' => '', // required
    'amount' => -50, // min: 0.01
    'academic_session_id' => -999, // not existing
    'expense_date' => Carbon::tomorrow()->toDateString(), // future date
    'expense_category_id' => null
  ];

  postJson(
    route('institutions.expenses.store', $this->institution->uuid),
    $invalidData
  )
    ->assertStatus(422)
    ->assertJsonValidationErrors([
      'title',
      'amount',
      'academic_session_id',
      'expense_date',
      'expense_category_id'
    ]);
});

it('can delete an expense', function () {
  $expense = Expense::factory()
    ->createdBy($this->institutionUser)
    ->for($this->academicSession)
    ->create();

  deleteJson(
    route('institutions.expenses.destroy', [
      $this->institution->uuid,
      $expense->id
    ])
  )->assertOk();

  assertSoftDeleted('expenses', [
    'id' => $expense->id
  ]);
});
