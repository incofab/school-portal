<?php

use App\Enums\WithdrawalStatus;
use App\Enums\PartnerUserRole;
use App\Models\BankAccount;
use App\Models\Partner;
use App\Models\User;
use App\Models\Withdrawal;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

it('loads the accountable morph details for manager withdrawals', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();
  $partnerUser = User::factory()
    ->partnerManager()
    ->create([
      'first_name' => 'Ada',
      'last_name' => 'Lovelace',
      'other_names' => ''
    ]);
  $partner = Partner::factory()->create([
    'user_id' => $partnerUser->id
  ]);
  $bankAccount = BankAccount::factory()
    ->accountable($partner)
    ->create();

  Withdrawal::query()->create([
    'bank_account_id' => $bankAccount->id,
    'withdrawable_type' => $partner->getMorphClass(),
    'withdrawable_id' => $partner->id,
    'amount' => 5000,
    'status' => WithdrawalStatus::Pending->value,
    'reference' => 'wd-partner-1'
  ]);

  actingAs($admin)
    ->get(route('managers.withdrawals.index'))
    ->assertInertia(function (AssertableInertia $page) use ($partnerUser) {
      $page
        ->component('managers/withdrawals/list-withdrawals')
        ->where(
          'withdrawals.data.0.withdrawable.user.full_name',
          $partnerUser->full_name
        );
    });
});

it('prevents partner staff from requesting withdrawals', function () {
  $partner = Partner::factory()->create();
  $staff = User::factory()
    ->partnerManager()
    ->create();
  $partner->partnerUsers()->create([
    'user_id' => $staff->id,
    'role' => PartnerUserRole::Staff->value
  ]);
  $bankAccount = BankAccount::factory()
    ->accountable($partner)
    ->create();

  actingAs($staff)
    ->post(route('managers.withdrawals.store'), [
      'bank_account_id' => $bankAccount->id,
      'amount' => 1000,
      'reference' => 'staff-withdrawal-attempt'
    ])
    ->assertForbidden();
});
