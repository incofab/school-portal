<?php

use App\Enums\WithdrawalStatus;
use App\Enums\PartnerUserRole;
use App\Models\BankAccount;
use App\Models\InstitutionGroup;
use App\Models\Institution;
use App\Models\Partner;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Models\Withdrawal;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;

beforeEach(function () {
  $this->admin = User::factory()
    ->adminManager()
    ->create();
});

function createManagerWithdrawal(
  Partner|InstitutionGroup $withdrawable,
  array $attributes = []
): Withdrawal {
  $bankAccount = BankAccount::factory()
    ->accountable($withdrawable)
    ->create();

  return Withdrawal::query()->create([
    'bank_account_id' => $bankAccount->id,
    'withdrawable_type' => $withdrawable->getMorphClass(),
    'withdrawable_id' => $withdrawable->id,
    'amount' => 25000,
    'status' => WithdrawalStatus::Pending->value,
    'reference' => fake()
      ->unique()
      ->bothify('wd-####'),
    ...$attributes
  ]);
}

it(
  'shows pending withdrawals in the admin dashboard attention summary',
  function () {
    $partner = Partner::factory()->create();
    createManagerWithdrawal($partner);
    createManagerWithdrawal($partner, [
      'status' => WithdrawalStatus::Paid->value
    ]);

    actingAs($this->admin)
      ->get(route('managers.dashboard'))
      ->assertInertia(function (AssertableInertia $page) {
        $page
          ->component('managers/dashboard')
          ->where('attentionSummary.pendingWithdrawalsCount', 1);
      });
  }
);

it(
  'shows each manager partner wallet balance on the managers list',
  function () {
    $partnerUser = User::factory()
      ->partnerManager()
      ->create();
    Partner::factory()->create([
      'user_id' => $partnerUser->id,
      'wallet' => 73500.5
    ]);
    InstitutionGroup::factory()->create([
      'partner_user_id' => $partnerUser->id
    ]);
    actingAs($this->admin)
      ->get(route('managers.index'))
      ->assertInertia(function (AssertableInertia $page) {
        $page
          ->component('managers/home/list-managers')
          ->where('managers.data.0.partner.wallet', 73500.5);
      });
  }
);

it('shows partner account analytics on the partner dashboard', function () {
  $partner = Partner::factory()->create([
    'name' => 'Growth Agents Ltd',
    'wallet' => 12500
  ]);
  $partnerUser = $partner->user;
  $staff = User::factory()
    ->partnerManager()
    ->create();
  $partner->partnerUsers()->create([
    'user_id' => $staff->id,
    'role' => PartnerUserRole::Staff->value
  ]);

  $institutionGroup = InstitutionGroup::factory()
    ->partner($staff)
    ->create();
  Institution::factory()->create([
    'institution_group_id' => $institutionGroup->id
  ]);
  RegistrationRequest::factory()
    ->partner($partnerUser)
    ->create();
  BankAccount::factory()
    ->accountable($partner)
    ->create();
  createManagerWithdrawal($partner);

  actingAs($partnerUser)
    ->get(route('managers.dashboard'))
    ->assertInertia(function (AssertableInertia $page) {
      $page
        ->component('managers/dashboard')
        ->where('partnerProfile.name', 'Growth Agents Ltd')
        ->where('partnerProfile.canUpdate', true)
        ->where('partnerAnalytics.institutionGroupsCount', 1)
        ->where('partnerAnalytics.institutionsCount', 1)
        ->where('partnerAnalytics.registrationRequestsCount', 1)
        ->where('partnerAnalytics.partnerUsersCount', 2)
        ->where('partnerAnalytics.bankAccountsCount', 2)
        ->where('partnerAnalytics.pendingWithdrawalsCount', 1)
        ->where('commissionBalance', 12500);
    });
});

it(
  'allows only partner admins to update their partner profile name',
  function () {
    $partner = Partner::factory()->create([
      'name' => 'Old Partner Name'
    ]);
    $staff = User::factory()
      ->partnerManager()
      ->create();
    $partner->partnerUsers()->create([
      'user_id' => $staff->id,
      'role' => PartnerUserRole::Staff->value
    ]);

    actingAs($staff)
      ->post(route('managers.partner-profile.update'), [
        'name' => 'Staff Attempt Ltd'
      ])
      ->assertForbidden();

    actingAs($partner->user)
      ->post(route('managers.partner-profile.update'), [
        'name' => 'Updated Partner Ltd'
      ])
      ->assertOk();

    $this->assertDatabaseHas('partners', [
      'id' => $partner->id,
      'name' => 'Updated Partner Ltd'
    ]);
  }
);
