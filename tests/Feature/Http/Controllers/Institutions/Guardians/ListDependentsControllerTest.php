<?php

use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->instAdmin = $this->institution->createdBy;

  $this->guardian = User::factory()
    ->guardian($this->institution)
    ->create();
  $this->guardianStudent = GuardianStudent::factory(4)
    ->withInstitution($this->institution)
    ->create(['guardian_user_id' => $this->guardian->id]);

  $this->route = route('institutions.guardians.list-dependents', [
    'institution' => $this->institution->uuid
  ]);
});

it('should show a list of dependent', function () {
  // Making the request to remove dependent
  actingAs($this->guardianStudent->first()->guardian)
    ->getJson($this->route)
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert->has('dependents.data', 4)
    );
});

it('should return 403 if user is not guardian ', function () {
  // Making the request to remove dependent
  actingAs($this->instAdmin)
    ->getJson($this->route)
    ->assertStatus(403);
});
