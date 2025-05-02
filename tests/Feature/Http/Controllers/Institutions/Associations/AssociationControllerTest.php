<?php

use App\Models\Association;
use App\Models\Institution;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
});

it('can list associations', function () {
  Association::factory(3)
    ->institution($this->institution)
    ->create();

  actingAs($this->admin)
    ->getJson(route('institutions.associations.index', [$this->institution]))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert
        ->has('associations', 3)
        ->component('institutions/associations/list-associations')
    );
});

it('can store an association', function () {
  $data = ['title' => 'Test Association'];

  actingAs($this->admin)
    ->postJson(
      route('institutions.associations.store', [$this->institution]),
      $data
    )
    ->assertOk();

  assertDatabaseHas('associations', [
    'institution_id' => $this->institution->id,
    'title' => 'Test Association'
  ]);
});

it('can update an association', function () {
  $association = Association::factory()
    ->institution($this->institution)
    ->create();

  $data = ['title' => 'Updated Association Name'];

  actingAs($this->admin)
    ->putJson(
      route('institutions.associations.update', [
        $this->institution,
        $association
      ]),
      $data
    )
    ->assertOk();

  assertDatabaseHas('associations', [
    'id' => $association->id,
    'title' => 'Updated Association Name'
  ]);
});

it('can delete an association', function () {
  $association = Association::factory()
    ->institution($this->institution)
    ->create();

  actingAs($this->admin);

  actingAs($this->admin)
    ->deleteJson(
      route('institutions.associations.destroy', [
        $this->institution,
        $association
      ])
    )
    ->assertOk();

  assertSoftDeleted('associations', ['id' => $association->id]);
});
