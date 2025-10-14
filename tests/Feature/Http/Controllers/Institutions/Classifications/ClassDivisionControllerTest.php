<?php

namespace Tests\Feature\Http\Controllers\Institutions\Classifications;

use App\Models\ClassDivision;
use App\Models\Institution;
use App\Models\Classification;

use function Pest\Laravel\{
  actingAs,
  assertDatabaseHas,
  assertDatabaseMissing,
  assertSoftDeleted
};

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
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

it('can add classifications to a class division', function () {
  $classDivision = ClassDivision::factory()
    ->withInstitution($this->institution)
    ->create();

  actingAs($this->admin)
    ->post(
      route('institutions.class-divisions.classifications.store', [
        $this->institution,
        $classDivision
      ]),
      [
        'classification_id' => $this->classification->id
      ]
    )
    ->assertOk();

  assertDatabaseHas('class_division_mappings', [
    'class_division_id' => $classDivision->id,
    'classification_id' => $this->classification->id
  ]);
});

it('can remove classifications from a class division', function () {
  $classDivision = ClassDivision::factory()
    ->withInstitution($this->institution)
    ->create();
  $classDivision->classifications()->attach($this->classification->id);

  actingAs($this->admin)
    ->delete(
      route('institutions.class-divisions.classifications.destroy', [
        $this->institution,
        $classDivision,
        $this->classification
      ])
    )
    ->assertOk();

  assertDatabaseMissing('class_division_mappings', [
    'class_division_id' => $classDivision->id,
    'classification_id' => $this->classification->id
  ]);
});
