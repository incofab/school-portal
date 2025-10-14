<?php

namespace Tests\Feature\Http\Controllers\Institutions\Classifications;

use App\Models\ClassDivision;
use App\Models\Institution;

use function Pest\Laravel\{
  actingAs,
  assertDatabaseHas,
  assertDatabaseMissing,
  assertSoftDeleted
};

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

it('can list class divisions', function () {
  ClassDivision::factory()
    ->withInstitution($this->institution)
    ->count(5)
    ->create();

  actingAs($this->admin)
    ->get(route('institutions.class-divisions.index', $this->institution))
    ->assertOk()
    ->assertInertia(
      fn($assert) => $assert
        ->component('institutions/class-divisions/list-class-divisions')
        ->has('classdivisions.data', 5)
    );
});

it('can create a class division', function () {
  actingAs($this->admin)
    ->post(route('institutions.class-divisions.store', $this->institution), [
      'title' => 'New Class Division'
    ])
    ->assertOk();

  assertDatabaseHas('class_divisions', [
    'institution_id' => $this->institution->id,
    'title' => 'New Class Division'
  ]);
});

it('can update a class division', function () {
  $classDivision = ClassDivision::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->put(
      route('institutions.class-divisions.update', [
        $this->institution,
        $classDivision
      ]),
      [
        'title' => 'Updated Class Division'
      ]
    )
    ->assertOk();

  assertDatabaseHas('class_divisions', [
    'id' => $classDivision->id,
    'title' => 'Updated Class Division'
  ]);
});

it('can delete a class division', function () {
  $classDivision = ClassDivision::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->delete(
      route('institutions.class-divisions.destroy', [
        $this->institution,
        $classDivision
      ])
    )
    ->assertOk();

  assertSoftDeleted('class_divisions', [
    'id' => $classDivision->id
  ]);
});
