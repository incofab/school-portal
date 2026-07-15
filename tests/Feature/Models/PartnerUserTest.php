<?php

use App\Actions\RecordUsers\BackfillPartnerUsers;
use App\Actions\RecordUsers\RecordPartner;
use App\Enums\Gender;
use App\Enums\PartnerUserRole;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('moves existing partner owners into partner users as admins', function () {
  $user = User::factory()
    ->partnerManager()
    ->create();
  $partnerId = DB::table('partners')->insertGetId([
    'user_id' => $user->id,
    'commission' => 30,
    'referral_id' => null,
    'referral_commission' => 0,
    'wallet' => 0,
    'created_at' => now(),
    'updated_at' => now()
  ]);

  BackfillPartnerUsers::run();

  $this->assertDatabaseHas('partner_users', [
    'partner_id' => $partnerId,
    'user_id' => $user->id,
    'role' => PartnerUserRole::Admin->value
  ]);
});

it('does not allow a user to belong to multiple partners', function () {
  $user = User::factory()
    ->partnerManager()
    ->create();
  $firstPartner = Partner::factory()->create(['user_id' => $user->id]);
  $secondPartner = Partner::factory()->create();

  expect(
    $firstPartner
      ->partnerUsers()
      ->where('user_id', $user->id)
      ->exists()
  )->toBeTrue();

  $secondPartner->partnerUsers()->create([
    'user_id' => $user->id,
    'role' => PartnerUserRole::Staff->value
  ]);
})->throws(QueryException::class);

it('resolves the partner for staff users through partner users', function () {
  $partner = Partner::factory()->create();
  $staff = User::factory()
    ->partnerManager()
    ->create();

  $partner->partnerUsers()->create([
    'user_id' => $staff->id,
    'role' => PartnerUserRole::Staff->value
  ]);

  expect($staff->fresh()->partner->is($partner))
    ->toBeTrue()
    ->and($partner->fresh()->users)
    ->toHaveCount(2);
});

it('creates an admin partner user when recording a new partner', function () {
  RecordPartner::make()->create([
    'first_name' => 'Pat',
    'last_name' => 'Ner',
    'other_names' => null,
    'phone' => '08030000000',
    'gender' => Gender::Male->value,
    'email' => 'partner@example.test',
    'username' => 'partner-admin',
    'password' => 'password',
    'role' => 'partner',
    'commission' => 30,
    'referral_email' => null,
    'referral_commission' => 0
  ]);

  $user = User::where('email', 'partner@example.test')->firstOrFail();
  $partner = $user->partner;

  expect($partner)->not->toBeNull();
  $this->assertDatabaseHas('partner_users', [
    'partner_id' => $partner->id,
    'user_id' => $user->id,
    'role' => PartnerUserRole::Admin->value
  ]);
});
