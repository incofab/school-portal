<?php

use App\Enums\WithdrawalStatus;
use App\Models\BankAccount;
use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\User;
use App\Models\Withdrawal;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->admin = User::factory()->adminManager()->create();
});

function createManagerWithdrawal(Partner|InstitutionGroup $withdrawable, array $attributes = []): Withdrawal
{
  $bankAccount = BankAccount::factory()
    ->accountable($withdrawable)
    ->create();

  return Withdrawal::query()->create([
    'bank_account_id' => $bankAccount->id,
    'withdrawable_type' => $withdrawable->getMorphClass(),
    'withdrawable_id' => $withdrawable->id,
    'amount' => 25000,
    'status' => WithdrawalStatus::Pending->value,
    'reference' => fake()->unique()->bothify('wd-####'),
    ...$attributes,
  ]);
}

it('shows pending withdrawals in the admin dashboard attention summary', function () {
  $partner = Partner::factory()->create();
  createManagerWithdrawal($partner);
  createManagerWithdrawal($partner, [
    'status' => WithdrawalStatus::Paid->value,
  ]);

  actingAs($this->admin)
    ->get(route('managers.dashboard'))
    ->assertInertia(function (AssertableInertia $page) {
      $page
        ->component('managers/dashboard')
        ->where('attentionSummary.pendingWithdrawalsCount', 1);
    });
});

it('shows each manager partner wallet balance on the managers list', function () {
  $partnerUser = User::factory()->partnerManager()->create();
  Partner::factory()->create([
    'user_id' => $partnerUser->id,
    'wallet' => 73500,
  ]);
  InstitutionGroup::factory()->create([
    'partner_user_id' => $partnerUser->id,
  ]);

  actingAs($this->admin)
    ->get(route('managers.index'))
    ->assertInertia(function (AssertableInertia $page) {
      $page
        ->component('managers/home/list-managers')
        ->where('managers.data.0.partner.wallet', 73500.0);
    });
});

