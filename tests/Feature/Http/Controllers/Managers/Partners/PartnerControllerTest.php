<?php

use App\Enums\Gender;
use App\Enums\PartnerUserRole;
use App\Models\Partner;
use App\Models\PartnerRegistrationRequest;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('lists partner accounts with their admins for admin managers', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();
  $partner = Partner::factory()->create([
    'name' => 'Growth Partners Ltd',
    'commission' => 25,
    'referral_commission' => 5
  ]);

  actingAs($admin)
    ->get(route('managers.partners.index'))
    ->assertOk()
    ->assertInertia(function (AssertableInertia $page) use ($partner) {
      $page
        ->component('managers/partners/list-partners')
        ->where('partners.data.0.id', $partner->id)
        ->where('partners.data.0.name', 'Growth Partners Ltd')
        ->where('partners.data.0.admin_users.0.id', $partner->user_id)
        ->where('partners.data.0.partner_users_count', 1);
    });
});

it('allows admin managers to update partner account information', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();
  $referral = Partner::factory()->create();
  $partner = Partner::factory()->create([
    'name' => 'Old Partner Ltd',
    'commission' => 20,
    'referral_commission' => 0
  ]);

  actingAs($admin)
    ->post(route('managers.partners.update', [$partner]), [
      'name' => 'New Partner Ltd',
      'commission' => 35,
      'referral_email' => $referral->user->email,
      'referral_commission' => 8
    ])
    ->assertOk();

  $this->assertDatabaseHas('partners', [
    'id' => $partner->id,
    'name' => 'New Partner Ltd',
    'commission' => 35,
    'referral_id' => $referral->id,
    'referral_commission' => 8
  ]);
});

it('prevents partner managers from updating global partner accounts', function () {
  $partner = Partner::factory()->create();
  $otherPartner = Partner::factory()->create();

  actingAs($partner->user)
    ->post(route('managers.partners.update', [$otherPartner]), [
      'name' => 'Unauthorized Change Ltd',
      'commission' => 40,
      'referral_email' => null,
      'referral_commission' => 0
    ])
    ->assertRedirect(route('user.dashboard'));

  $this->assertDatabaseMissing('partners', [
    'id' => $otherPartner->id,
    'name' => 'Unauthorized Change Ltd'
  ]);
});

it('stores the partner account name when approving partner registration requests', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();
  $request = PartnerRegistrationRequest::query()->create([
    'first_name' => 'Ada',
    'last_name' => 'Partner',
    'other_names' => null,
    'phone' => '08030000001',
    'email' => 'ada-partner@example.test',
    'referral_id' => null,
    'username' => 'ada-partner',
    'gender' => Gender::Female->value,
    'password' => 'password',
    'reference' => 'partner-request-1'
  ]);

  actingAs($admin)
    ->post(route('managers.partner-registration-requests.onboard', [$request]), [
      'name' => 'Ada Growth Network',
      'commission' => 30,
      'referral_email' => null,
      'referral_commission' => 0
    ])
    ->assertOk();

  $this->assertDatabaseHas('partners', [
    'name' => 'Ada Growth Network',
    'commission' => 30,
    'referral_commission' => 0
  ]);
  $partner = Partner::query()
    ->where('name', 'Ada Growth Network')
    ->firstOrFail();
  $this->assertDatabaseHas('partner_users', [
    'partner_id' => $partner->id,
    'role' => PartnerUserRole::Admin->value
  ]);
  $this->assertSoftDeleted('partner_registration_requests', [
    'id' => $request->id
  ]);
});
