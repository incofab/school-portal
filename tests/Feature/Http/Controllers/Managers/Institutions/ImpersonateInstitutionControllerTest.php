<?php

use App\Models\Institution;
use App\Models\InstitutionGroup;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

it('allows an admin manager to impersonate an institution admin', function () {
  $manager = User::factory()
    ->adminManager()
    ->create();
  $institution = Institution::factory()->create();
  $manager->refresh();
  app(PermissionRegistrar::class)->forgetCachedPermissions();

  actingAs($manager)
    ->get(route('institutions.impersonate', $institution->uuid))
    ->assertRedirect(route('user.dashboard'));

  expect(auth()->id())
    ->toBe($institution->user_id)
    ->and(session('impersonator_id'))
    ->toBe($manager->id)
    ->and(session('impersonator_type'))
    ->toBe('manager');
});

it(
  'allows the institution group partner to impersonate an institution admin',
  function () {
    $partner = User::factory()
      ->partnerManager()
      ->create();
    $institutionGroup = InstitutionGroup::factory()
      ->partner($partner)
      ->create();
    $institution = Institution::factory()->create([
      'institution_group_id' => $institutionGroup->id
    ]);
    $partner->refresh();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($partner)
      ->get(route('institutions.impersonate', $institution->uuid))
      ->assertRedirect(route('user.dashboard'));
  }
);

it(
  'does not allow an unrelated partner to impersonate an institution',
  function () {
    $partner = User::factory()
      ->partnerManager()
      ->create();
    $institution = Institution::factory()->create();
    $partner->refresh();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    actingAs($partner)
      ->get(route('institutions.impersonate', $institution->uuid))
      ->assertForbidden();
  }
);
