<?php

use App\Enums\InstitutionUserType;
use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Association;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Student;
use App\Support\MorphMap;
use App\Support\SettingsHandler;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
  // Create a user and assign the admin role
  $this->admin = InstitutionUser::factory()->create([
    'role' => InstitutionUserType::Admin
  ]);
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->academicSession = AcademicSession::factory()->create();

  actingAs($this->admin);
});

test('index displays list of fees', function () {
  $settingsHandler = SettingsHandler::makeFromInstitution($this->institution);
  $fees = Fee::factory(3)
    ->for($this->institution)
    ->has(
      FeeCategory::factory()
        ->for($this->institution)
        ->state(function (array $attributes, Fee $fee) {
          return [
            'fee_id' => $fee->id,
            'feeable_type' => MorphMap::key(Institution::class),
            'feeable_id' => $fee->institution_id
          ];
        }),
      'feeCategories'
    )
    ->create([
      'academic_session_id' => $settingsHandler->getCurrentAcademicSession(),
      'term' => $settingsHandler->getCurrentTerm()
    ]);

  getJson(route('institutions.fees.index', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/payments/list-fees')
        ->has('fees')
        ->has('fees.data', 3)
        ->has('fees.data.0.fee_categories') // Ensure categories are loaded
        ->has('fees.data.0.fee_categories.0.feeable') // Ensure feeable is loaded
    );

  getJson(route('institutions.fees.search', $this->institution))->assertOk();
});

test('create displays the fee creation form', function () {
  Association::factory(2)
    ->for($this->institution)
    ->create();
  Classification::factory(2)
    ->withInstitution($this->institution)
    ->create();

  getJson(route('institutions.fees.create', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/payments/create-edit-fee')
        ->has('associations', 2)
        ->has('classificationGroups', 2)
        ->has('classifications', 2)
        ->missing('fee') // No fee should be passed on create
    );
});

test('store creates a new fee using RecordFee action', function () {
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();

  $feeData = [
    'title' => 'New Test Fee',
    'amount' => 1500.0,
    'payment_interval' => PaymentInterval::Termly->value,
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'fee_items' => [['title' => 'Item 1', 'amount' => 1500]],
    'fee_categories' => [
      [
        'feeable_type' => MorphMap::key(Classification::class),
        'feeable_id' => $classification->id
      ]
    ]
  ];

  postJson(
    route('institutions.fees.store', $this->institution),
    $feeData
  )->assertOk(); // Check if the mocked fee is returned

  assertDatabaseHas('fees', [
    'title' => $feeData['title'],
    'institution_id' => $this->institution->id
  ]);
  assertDatabaseHas('fee_categories', [
    'feeable_type' => MorphMap::key(Classification::class),
    'feeable_id' => $classification->id
  ]);
});

test('edit displays the fee editing form with fee data', function () {
  $fee = Fee::factory()
    ->for($this->institution)
    ->has(
      FeeCategory::factory()
        ->count(1)
        ->for($this->institution)
        ->state(function (array $attributes, Fee $fee) {
          return [
            'fee_id' => $fee->id,
            'feeable_type' => MorphMap::key(Institution::class),
            'feeable_id' => $fee->institution_id
          ];
        }),
      'feeCategories'
    )
    ->create();

  Association::factory(1)
    ->for($this->institution)
    ->create();
  ClassificationGroup::factory(1)
    ->for($this->institution)
    ->create();
  Classification::factory(1)
    ->for($this->institution)
    ->create();

  getJson(route('institutions.fees.edit', [$this->institution, $fee]))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/payments/create-edit-fee')
        ->has('associations', 1)
        ->has('classificationGroups', 1)
        ->has('classifications', 1)
        ->where('fee.id', $fee->id)
        ->where('fee.title', $fee->title)
        ->has('fee.fee_categories', 1) // Ensure categories loaded
        ->has('fee.fee_categories.0.feeable') // Ensure feeable loaded
    );
});

test('update modifies an existing fee using RecordFee action', function () {
  $fee = Fee::factory()
    ->for($this->institution)
    ->create(['title' => 'Old Title']);
  $classification = Classification::factory()
    ->for($this->institution)
    ->create();

  $updateData = [
    'title' => 'Updated Test Fee',
    'amount' => 2500.0,
    'payment_interval' => PaymentInterval::Sessional->value,
    'fee_items' => [['title' => 'Updated Item', 'amount' => 2500]],
    'fee_categories' => [
      [
        'feeable_type' => MorphMap::key(Classification::class),
        'feeable_id' => $classification->id
      ]
    ]
  ];

  putJson(
    route('institutions.fees.update', [$this->institution, $fee]),
    $updateData
  )
    ->assertOk()
    ->assertJsonStructure([]); // Update returns simple ok()

  assertDatabaseHas('fees', ['id' => $fee->id, 'title' => 'Updated Test Fee']);
});

test('destroy soft deletes a fee', function () {
  $fee = Fee::factory()
    ->for($this->institution)
    ->create();

  deleteJson(
    route('institutions.fees.destroy', [$this->institution, $fee])
  )->assertOk();

  assertSoftDeleted('fees', ['id' => $fee->id]);
});
