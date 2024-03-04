<?php

use App\Models\InstitutionGroup;
use App\Models\RegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->registrationRequest = RegistrationRequest::factory()->create();

  $this->requestData = [
    'name' => fake()->name()
  ];
});

it('creates an institution group from registration request', function () {
  $ordinaryUser = User::factory()->create();
  $anotherPartner = User::factory()
    ->partnerManager()
    ->create();
  $partner = $this->registrationRequest->partner;

  actingAs($ordinaryUser)
    ->postJson(
      route('managers.registration-requests.institution-groups.store', [
        $this->registrationRequest
      ]),
      $this->requestData
    )
    ->assertForbidden();

  actingAs($anotherPartner)
    ->postJson(
      route('managers.registration-requests.institution-groups.store', [
        $this->registrationRequest
      ]),
      $this->requestData
    )
    ->assertForbidden();

  actingAs($partner)
    ->postJson(
      route('managers.registration-requests.institution-groups.store', [
        $this->registrationRequest
      ]),
      $this->requestData
    )
    ->assertOk();

  $institutionGroup = $partner
    ->partnerInstitutionGroups()
    ->with('user')
    ->first();
  expect($institutionGroup)
    ->not()
    ->toBeNull()
    ->user->first_name->toBe($this->registrationRequest->data['first_name']);
});

it('creates an institution from registration request', function () {
  $partner = $this->registrationRequest->partner;
  $institutionGroup = InstitutionGroup::factory()
    ->partner($partner)
    ->create();

  $ordinaryUser = User::factory()->create();
  $anotherPartner = User::factory()
    ->partnerManager()
    ->create();

  actingAs($ordinaryUser)
    ->postJson(
      route('managers.registration-requests.institutions.store', [
        $institutionGroup,
        $this->registrationRequest
      ]),
      $this->requestData
    )
    ->assertForbidden();

  actingAs($anotherPartner)
    ->postJson(
      route('managers.registration-requests.institutions.store', [
        $institutionGroup,
        $this->registrationRequest
      ]),
      $this->requestData
    )
    ->assertForbidden();

  actingAs($partner)
    ->postJson(
      route('managers.registration-requests.institutions.store', [
        $institutionGroup,
        $this->registrationRequest
      ]),
      $this->requestData
    )
    ->assertOk();

  $createdInstitution = $institutionGroup->institutions()->first();
  expect($createdInstitution)
    ->not()
    ->toBeNull()
    ->user_id->toBe($institutionGroup->user_id);
});
