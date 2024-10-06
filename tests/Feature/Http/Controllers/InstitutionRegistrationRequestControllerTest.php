<?php

use App\Models\Institution;
use App\Models\RegistrationRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

beforeEach(function () {
  seed(RoleSeeder::class);
  $this->requestData = [
    'reference' => Str::orderedUuid(),
    ...User::factory()
      ->make()
      ->only(['first_name', 'last_name', 'other_names', 'email', 'phone']),
    'password' => 'password',
    'password_confirmation' => 'password',
    'institution' => Institution::factory()
      ->make()
      ->only(['name', 'phone', 'email', 'address'])
  ];
  $this->partner = User::factory()
    ->partnerManager()
    ->create();
});

it('displays institution registration request page', function () {
  getJson(route('registration-requests.create'))->assertOk();
});

it(
  'creates an institution registration request with the partner link',
  function () {
    postJson(
      route('registration-requests.store', [$this->partner]),
      $this->requestData
    )
      // ->dump()
      ->assertRedirect();

    expect(
      RegistrationRequest::query()
        ->where('reference', $this->requestData['reference'])
        ->first()
    )
      ->not()
      ->toBeNull()
      ->partner_user_id->toBe($this->partner->id);
  }
);

it(
  'creates an institution registration request when the is no partner link supplied',
  function () {
    postJson(
      route('registration-requests.store'),
      $this->requestData
    )->assertStatus(404);
    $adminManager = User::factory()
      ->adminManager()
      ->create();
    postJson(route('registration-requests.store'), $this->requestData)
      // ->dump()
      ->assertRedirect();

    expect(
      RegistrationRequest::query()
        ->where('reference', $this->requestData['reference'])
        ->first()
    )
      ->not()
      ->toBeNull()
      ->partner_user_id->toBe($adminManager->id);
  }
);

it('displays institution registration success message page', function () {
  $registrationRequest = RegistrationRequest::factory()->create();
  getJson(
    route('registration-requests.completed-message', [$registrationRequest])
  )->assertOk();
});
