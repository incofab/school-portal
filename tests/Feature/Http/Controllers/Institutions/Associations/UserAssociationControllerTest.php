<?php

use App\Models\Association;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Models\UserAssociation;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->association = Association::factory()
    ->institution($this->institution)
    ->create();
});

it('can list user associations', function () {
  $user = User::factory()->create();
  [$institutionUser1, $institutionUser2] = InstitutionUser::factory()
    ->student($this->institution)
    ->create();

  UserAssociation::factory()
    ->association($this->association, $institutionUser1)
    ->create();
  UserAssociation::factory()
    ->association($this->association, $institutionUser2)
    ->create();

  actingAs($this->admin)
    ->getJson(
      route('institutions.user-associations.index', [
        $this->institution,
        $this->association
      ])
    )
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $assert) => $assert
        ->has('userAssociations.data', 2)
        ->component('institutions/associations/list-user-associations')
    );
});

it('can store user associations', function () {
  $institutionUser1 = InstitutionUser::factory()
    ->student($this->institution)
    ->create();
  $institutionUser2 = InstitutionUser::factory()
    ->student($this->institution)
    ->create();

  $data = [
    'association_id' => $this->association->id,
    'institution_user_ids' => [$institutionUser1->id, $institutionUser2->id]
  ];

  actingAs($this->admin)
    ->postJson(
      route('institutions.user-associations.store', [$this->institution]),
      $data
    )
    ->assertOk();

  assertDatabaseHas('user_associations', [
    'institution_id' => $this->institution->id,
    'association_id' => $this->association->id,
    'institution_user_id' => $institutionUser1->id
  ]);

  assertDatabaseHas('user_associations', [
    'institution_id' => $this->institution->id,
    'association_id' => $this->association->id,
    'institution_user_id' => $institutionUser2->id
  ]);
});

it('can delete a user association', function () {
  $institutionUser = InstitutionUser::factory()
    ->student($this->institution)
    ->create();

  $userAssociation = UserAssociation::factory()
    ->institutionUser($institutionUser, $this->association)
    ->create();

  actingAs($this->admin)
    ->deleteJson(
      route('institutions.user-associations.destroy', [
        $this->institution,
        $userAssociation
      ])
    )
    ->assertOk();

  assertDatabaseMissing('user_associations', ['id' => $userAssociation->id]);
});
