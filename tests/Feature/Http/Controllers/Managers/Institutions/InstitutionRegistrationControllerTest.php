<?php

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
  $this->manager = User::factory()
    ->partnerManager()
    ->create();
  $this->institutionGroup = InstitutionGroup::factory()
    ->partner($this->manager)
    ->create();
  actingAs($this->manager);
});

it('returns inertia view with correct props for create method', function () {
  InstitutionGroup::factory(2)
    ->partner($this->manager)
    ->create();

  getJson(
    route('managers.institutions.create', [
      'institutionGroup' => $this->institutionGroup->id
    ])
  )
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('managers/institutions/create-institution')
        ->where('institutionGroup.id', $this->institutionGroup->id)
        ->has('institutionGroups', 3)
    );
});

it('stores a new institution and redirects', function () {
  $data = [
    'name' => 'Test Institution',
    'email' => 'institution@example.com',
    'phone' => '08012345678',
    'address' => '123 Test Lane',
    'institution_group_id' => $this->institutionGroup->id
  ];

  // Post to the store route
  postJson(route('managers.institutions.store'), $data)->assertRedirect(
    route('managers.institutions.index')
  );

  // Assert institution was created
  $this->assertDatabaseHas('institutions', [
    'name' => 'Test Institution',
    'email' => 'institution@example.com',
    'institution_group_id' => $this->institutionGroup->id
  ]);

  // Optional: assert pivot table role
  $institution = Institution::where('name', 'Test Institution')->first();
  expect($institution)->not->toBeNull();
  $this->assertEquals(
    InstitutionUserType::Admin,
    $institution->institutionUsers()->first()->role
  );
});
