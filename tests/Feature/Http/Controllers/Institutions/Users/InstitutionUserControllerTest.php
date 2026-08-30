<?php

use App\Models\Institution;
use App\Models\InstitutionUser;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

it('provides the institution identity for the staff id card page', function () {
  InstitutionUser::factory()
    ->teacher($this->institution)
    ->create();

  actingAs($this->admin)
    ->get(route('institutions.users.idcards', $this->institution->uuid))
    ->assertOk()
    ->assertInertia(
      fn(Assert $page) => $page
        ->component('institutions/students/staff-id-cards')
        ->where('shared__currentInstitution.uuid', $this->institution->uuid)
        ->has('persons', 2)
    );
});
