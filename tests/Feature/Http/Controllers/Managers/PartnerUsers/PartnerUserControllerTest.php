<?php

use App\Enums\Gender;
use App\Enums\ManagerRole;
use App\Enums\PartnerUserRole;
use App\Models\Partner;
use App\Models\User;

use function Pest\Laravel\actingAs;

function partnerUserPayload(array $overrides = []): array
{
  return [
    'first_name' => 'Partner',
    'last_name' => 'Staff',
    'other_names' => null,
    'phone' => '08030000001',
    'gender' => Gender::Male->value,
    'email' => 'partner-staff@example.test',
    'username' => 'partner-staff',
    'password' => 'password',
    'password_confirmation' => 'password',
    'role' => PartnerUserRole::Staff->value,
    ...$overrides
  ];
}

it('allows a partner admin to register a user into their partner account', function () {
  $partner = Partner::factory()->create();
  $admin = $partner->user;

  actingAs($admin)
    ->post(route('managers.partner-users.store'), partnerUserPayload())
    ->assertOk();

  $user = User::where('email', 'partner-staff@example.test')->firstOrFail();

  expect($user->hasRole(ManagerRole::Partner))->toBeTrue();
  $this->assertDatabaseHas('partner_users', [
    'partner_id' => $partner->id,
    'user_id' => $user->id,
    'role' => PartnerUserRole::Staff->value
  ]);
});

it('prevents partner staff from registering partner users', function () {
  $partner = Partner::factory()->create();
  $staff = User::factory()
    ->partnerManager()
    ->create();
  $partner->partnerUsers()->create([
    'user_id' => $staff->id,
    'role' => PartnerUserRole::Staff->value
  ]);

  actingAs($staff)
    ->post(route('managers.partner-users.store'), partnerUserPayload())
    ->assertForbidden();
});

it('allows a partner admin to change a partner user role', function () {
  $partner = Partner::factory()->create();
  $staff = User::factory()
    ->partnerManager()
    ->create();
  $partnerUser = $partner->partnerUsers()->create([
    'user_id' => $staff->id,
    'role' => PartnerUserRole::Staff->value
  ]);

  actingAs($partner->user)
    ->post(route('managers.partner-users.update', [$partnerUser]), [
      'role' => PartnerUserRole::Admin->value
    ])
    ->assertOk();

  $this->assertDatabaseHas('partner_users', [
    'id' => $partnerUser->id,
    'role' => PartnerUserRole::Admin->value
  ]);
});

it('prevents partner admins from updating another partner account user', function () {
  $partner = Partner::factory()->create();
  $otherPartner = Partner::factory()->create();

  actingAs($partner->user)
    ->post(route('managers.partner-users.update', [
      $otherPartner->partnerUsers()->first()
    ]), [
      'role' => PartnerUserRole::Staff->value
    ])
    ->assertForbidden();
});

it('prevents demoting the last partner admin', function () {
  $partner = Partner::factory()->create();
  $partnerUser = $partner->partnerUsers()->first();

  actingAs($partner->user)
    ->post(route('managers.partner-users.update', [$partnerUser]), [
      'role' => PartnerUserRole::Staff->value
    ])
    ->assertStatus(422);
});
