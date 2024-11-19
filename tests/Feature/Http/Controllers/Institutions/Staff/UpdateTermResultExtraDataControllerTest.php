<?php

use App\Models\Institution;
use App\Models\TermResult;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->termResult = TermResult::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->route = route('institutions.term-results.extra-data.update', [
    $this->institution->uuid,
    $this->termResult->id
  ]);
});

it('updates term result extra data with valid input', function () {
  $payload = [
    'weight' => 'invalid', // Not a numeric value
    'height' => 'invalid' // Not a numeric value
  ];
  actingAs($this->instAdmin)
    ->postJson($this->route, $payload)
    ->assertStatus(422)
    ->assertJsonValidationErrors(['weight', 'height']);

  $payload = [
    'weight' => 60.5,
    'height' => 170.2
  ];

  actingAs($this->instAdmin)
    ->postJson($this->route, $payload)
    ->assertOk();

  expect($this->termResult->refresh())
    ->weight->toBe(60.5)
    ->height->toBe(170.2);
});

it('restricts access for unauthorized roles', function () {
  $user = User::factory()
    ->student($this->institution)
    ->create();
  $payload = [
    'weight' => 60.5,
    'height' => 170.2
  ];
  actingAs($user)
    ->postJson($this->route, $payload)
    ->assertForbidden();
});
