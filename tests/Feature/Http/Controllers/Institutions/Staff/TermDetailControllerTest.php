<?php

use App\Models\Institution;
use App\Models\InstitutionSetting;
use App\Models\TermDetail;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;
  $this->getRoute = route('institutions.term-details.index', [
    'institution' => $this->institution->uuid
  ]);
  $this->seedSetting = fn() => InstitutionSetting::factory()
    ->withInstitution($this->institution)
    ->term()
    ->academicSession()
    ->create();
  $this->createTermDetail = function () {
    $this->termDetail = TermDetail::factory()
      ->for($this->institution)
      ->create();
    $this->updateRoute = route('institutions.term-details.update', [
      $this->institution->uuid,
      $this->termDetail->id
    ]);
  };
});

it('renders the term details page with expected data', function () {
  actingAs($this->instAdmin)
    ->get($this->getRoute)
    ->assertStatus(401);
  ($this->seedSetting)();
  $studentUser = User::factory()
    ->student($this->institution)
    ->create();
  actingAs($studentUser)
    ->get($this->getRoute)
    ->assertForbidden();

  actingAs($this->instAdmin)
    ->get($this->getRoute)
    ->assertOk()
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/term-details/list-term-details')
        ->has('termDetail')
        ->has('termDetails')
    );
});

it('updates a term detail with valid data', function () {
  ($this->createTermDetail)();
  $payload = [
    'expected_attendance_count' => 25,
    'start_date' => now()
      ->subDays(5)
      ->toDateString(),
    'end_date' => now()->toDateString()
  ];

  actingAs($this->instAdmin)
    ->putJson($this->updateRoute, $payload)
    ->assertOk();
  // dd($this->termDetail->fresh()->toArray());
  expect($this->termDetail->refresh())
    ->expected_attendance_count->toBe(25)
    ->start_date->toDateString()
    ->toBe($payload['start_date'])
    ->end_date->toDateString()
    ->toBe($payload['end_date']);
});

it('validates update term detail payload', function () {
  ($this->createTermDetail)();
  $payload = [
    'expected_attendance_count' => 'not-an-integer',
    'start_date' => 'invalid-date',
    'end_date' => 'invalid-date'
  ];
  actingAs($this->instAdmin)
    ->putJson($this->updateRoute, $payload)
    ->assertJsonValidationErrors([
      'expected_attendance_count',
      'start_date',
      'end_date'
    ]);
});
