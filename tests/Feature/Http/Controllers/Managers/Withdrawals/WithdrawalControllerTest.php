<?php

use App\Enums\PartnerUserRole;
use App\Enums\PayoutMerchantType;
use App\Enums\PayoutStatus;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\WithdrawalStatus;
use App\Models\BankAccount;
use App\Models\Payout;
use App\Models\Partner;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;
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

it('allows admins to pay unprocessed withdrawals through monnify', function () {
  config(['services.monnify.disbursement-account-number' => '9999999999']);

  $admin = User::factory()
    ->adminManager()
    ->create();
  $partner = Partner::factory()->create();
  $bankAccount = BankAccount::factory()
    ->accountable($partner)
    ->create([
      'bank_code' => '058',
      'account_number' => '0123456789',
      'account_name' => 'Ada Lovelace'
    ]);
  $withdrawal = Withdrawal::query()->create([
    'bank_account_id' => $bankAccount->id,
    'withdrawable_type' => $partner->getMorphClass(),
    'withdrawable_id' => $partner->id,
    'amount' => 5000,
    'status' => WithdrawalStatus::Pending->value,
    'reference' => 'wd-success'
  ]);

  Http::fake([
    'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
      'requestSuccessful' => true,
      'responseBody' => ['accessToken' => 'token']
    ]),
    'https://sandbox.monnify.com/api/v2/disbursements/single' => Http::response(
      [
        'requestSuccessful' => true,
        'responseBody' => [
          'reference' => "edumanager-withdrawal-{$withdrawal->id}",
          'status' => 'SUCCESS',
          'transactionDescription' => 'Transfer Successful'
        ]
      ]
    )
  ]);

  actingAs($admin)
    ->post(route('managers.withdrawals.pay-unprocessed'))
    ->assertOk()
    ->assertJson([
      'success' => true,
      'message' => '1 processed: 1 successful, 0 pending, 0 failed.'
    ]);

  $withdrawal->refresh();
  $payout = $withdrawal->payout;

  expect($withdrawal->status)
    ->toBe(WithdrawalStatus::Paid)
    ->and($withdrawal->processed_by_user_id)
    ->toBe($admin->id)
    ->and($payout)
    ->not->toBeNull()
    ->and($payout->reference)
    ->toBe("edumanager-withdrawal-{$withdrawal->id}")
    ->and($payout->status)
    ->toBe(PayoutStatus::Successful)
    ->and($payout->merchant_status)
    ->toBe('SUCCESS')
    ->and($payout->is_processing)
    ->toBeFalse()
    ->and($payout->attempt_count)
    ->toBe(1)
    ->and($withdrawal->paid_at)
    ->not->toBeNull()
    ->and($payout->completed_at)
    ->not->toBeNull();

  Http::assertSent(function ($request) use ($withdrawal) {
    return $request->url() ===
      'https://sandbox.monnify.com/api/v2/disbursements/single' &&
      $request['reference'] === "edumanager-withdrawal-{$withdrawal->id}" &&
      $request['destinationAccountName'] === 'Ada Lovelace' &&
      $request['sourceAccountNumber'] === '9999999999';
  });
});

it(
  'keeps pending monnify payouts unprocessed for later status checks',
  function () {
    config(['services.monnify.disbursement-account-number' => '9999999999']);

    $admin = User::factory()
      ->adminManager()
      ->create();
    $partner = Partner::factory()->create();
    $bankAccount = BankAccount::factory()
      ->accountable($partner)
      ->create([
        'bank_code' => '058',
        'account_number' => '0123456789',
        'account_name' => 'Ada Lovelace'
      ]);
    $withdrawal = Withdrawal::query()->create([
      'bank_account_id' => $bankAccount->id,
      'withdrawable_type' => $partner->getMorphClass(),
      'withdrawable_id' => $partner->id,
      'amount' => 5000,
      'status' => WithdrawalStatus::Pending->value,
      'reference' => 'wd-pending'
    ]);

    Http::fake([
      'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
        'requestSuccessful' => true,
        'responseBody' => ['accessToken' => 'token']
      ]),
      'https://sandbox.monnify.com/api/v2/disbursements/single' => Http::response(
        [
          'requestSuccessful' => true,
          'responseBody' => [
            'reference' => "edumanager-withdrawal-{$withdrawal->id}",
            'status' => 'PENDING_AUTHORIZATION',
            'transactionDescription' => 'Awaiting authorization'
          ]
        ]
      )
    ]);

    actingAs($admin)
      ->post(route('managers.withdrawals.pay-unprocessed'))
      ->assertOk()
      ->assertJson([
        'message' => '1 processed: 0 successful, 1 pending, 0 failed.'
      ]);

    $withdrawal->refresh();
    $payout = $withdrawal->payout;

    expect($withdrawal->status)
      ->toBe(WithdrawalStatus::Pending)
      ->and($withdrawal->paid_at)
      ->toBeNull()
      ->and($payout->status)
      ->toBe(PayoutStatus::Pending)
      ->and($payout->merchant_status)
      ->toBe('PENDING_AUTHORIZATION')
      ->and($payout->reference)
      ->toBe("edumanager-withdrawal-{$withdrawal->id}")
      ->and($payout->is_processing)
      ->toBeFalse()
      ->and($payout->attempt_count)
      ->toBe(1);
  }
);

it(
  'checks monnify status before retrying a withdrawal with an existing reference',
  function () {
    config(['services.monnify.disbursement-account-number' => '9999999999']);

    $admin = User::factory()
      ->adminManager()
      ->create();
    $partner = Partner::factory()->create();
    $bankAccount = BankAccount::factory()
      ->accountable($partner)
      ->create([
        'bank_code' => '058',
        'account_number' => '0123456789',
        'account_name' => 'Ada Lovelace'
      ]);
    $withdrawal = Withdrawal::query()->create([
      'bank_account_id' => $bankAccount->id,
      'withdrawable_type' => $partner->getMorphClass(),
      'withdrawable_id' => $partner->id,
      'amount' => 5000,
      'status' => WithdrawalStatus::Pending->value,
      'reference' => 'wd-unknown'
    ]);
    $payout = Payout::query()->create([
      'payoutable_type' => $withdrawal->getMorphClass(),
      'payoutable_id' => $withdrawal->id,
      'purpose' => 'withdrawal',
      'merchant' => PayoutMerchantType::Monnify,
      'status' => PayoutStatus::Unknown,
      'merchant_status' => 'UNKNOWN',
      'reference' => 'edumanager-withdrawal-known',
      'amount' => $withdrawal->amount,
      'currency' => 'NGN',
      'is_processing' => false,
      'attempt_count' => 1,
      'attempted_at' => now(),
      'note' => 'Previous request status unknown.'
    ]);

    Http::fake([
      'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
        'requestSuccessful' => true,
        'responseBody' => ['accessToken' => 'token']
      ]),
      'https://sandbox.monnify.com/api/v2/disbursements/single/summary?reference=edumanager-withdrawal-known' => Http::response(
        [
          'requestSuccessful' => true,
          'responseBody' => [
            'reference' => 'edumanager-withdrawal-known',
            'status' => 'SUCCESS',
            'transactionDescription' => 'Transfer Successful'
          ]
        ]
      ),
      'https://sandbox.monnify.com/api/v2/disbursements/single' => Http::response(
        [],
        500
      )
    ]);

    actingAs($admin)
      ->post(route('managers.withdrawals.pay-unprocessed'))
      ->assertOk()
      ->assertJson([
        'message' => '1 processed: 1 successful, 0 pending, 0 failed.'
      ]);

    expect($withdrawal->refresh()->status)->toBe(WithdrawalStatus::Paid);
    expect($payout->refresh()->attempt_count)->toBe(1);

    Http::assertNotSent(
      fn($request) => $request->method() === 'POST' &&
        $request->url() ===
          'https://sandbox.monnify.com/api/v2/disbursements/single'
    );
  }
);

it(
  'allows admins to pay a single unprocessed withdrawal through monnify',
  function () {
    config(['services.monnify.disbursement-account-number' => '9999999999']);

    $admin = User::factory()
      ->adminManager()
      ->create();
    $partner = Partner::factory()->create();
    $bankAccount = BankAccount::factory()
      ->accountable($partner)
      ->create([
        'bank_code' => '058',
        'account_number' => '0123456789',
        'account_name' => 'Ada Lovelace'
      ]);
    $withdrawal = Withdrawal::query()->create([
      'bank_account_id' => $bankAccount->id,
      'withdrawable_type' => $partner->getMorphClass(),
      'withdrawable_id' => $partner->id,
      'amount' => 5000,
      'status' => WithdrawalStatus::Pending->value,
      'reference' => 'wd-single'
    ]);

    Http::fake([
      'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
        'requestSuccessful' => true,
        'responseBody' => ['accessToken' => 'token']
      ]),
      'https://sandbox.monnify.com/api/v2/disbursements/single' => Http::response(
        [
          'requestSuccessful' => true,
          'responseBody' => [
            'reference' => "edumanager-withdrawal-{$withdrawal->id}",
            'status' => 'SUCCESS',
            'transactionDescription' => 'Transfer Successful'
          ]
        ]
      )
    ]);

    actingAs($admin)
      ->post(route('managers.withdrawals.pay', [$withdrawal]))
      ->assertOk()
      ->assertJson([
        'message' => '1 processed: 1 successful, 0 pending, 0 failed.'
      ]);

    $payout = $withdrawal->refresh()->payout;

    expect($withdrawal->status)
      ->toBe(WithdrawalStatus::Paid)
      ->and($payout->status)
      ->toBe(PayoutStatus::Successful)
      ->and($payout->attempt_count)
      ->toBe(1);
  }
);

it('does not start another transfer while a payout is processing', function () {
  config(['services.monnify.disbursement-account-number' => '9999999999']);

  $admin = User::factory()
    ->adminManager()
    ->create();
  $partner = Partner::factory()->create();
  $bankAccount = BankAccount::factory()
    ->accountable($partner)
    ->create([
      'bank_code' => '058',
      'account_number' => '0123456789',
      'account_name' => 'Ada Lovelace'
    ]);
  $withdrawal = Withdrawal::query()->create([
    'bank_account_id' => $bankAccount->id,
    'withdrawable_type' => $partner->getMorphClass(),
    'withdrawable_id' => $partner->id,
    'amount' => 5000,
    'status' => WithdrawalStatus::Pending->value,
    'reference' => 'wd-processing'
  ]);
  $payout = Payout::query()->create([
    'payoutable_type' => $withdrawal->getMorphClass(),
    'payoutable_id' => $withdrawal->id,
    'purpose' => 'withdrawal',
    'merchant' => PayoutMerchantType::Monnify,
    'status' => PayoutStatus::Pending,
    'merchant_status' => 'PENDING_AUTHORIZATION',
    'reference' => "edumanager-withdrawal-{$withdrawal->id}",
    'amount' => $withdrawal->amount,
    'currency' => 'NGN',
    'is_processing' => true,
    'attempt_count' => 1,
    'attempted_at' => now(),
    'note' => 'Awaiting authorization.'
  ]);

  Http::fake([
    'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
      'requestSuccessful' => true,
      'responseBody' => ['accessToken' => 'token']
    ]),
    "https://sandbox.monnify.com/api/v2/disbursements/single/summary?reference={$payout->reference}" => Http::response(
      [
        'requestSuccessful' => true,
        'responseBody' => [
          'reference' => $payout->reference,
          'status' => 'PENDING_AUTHORIZATION',
          'transactionDescription' => 'Awaiting authorization'
        ]
      ]
    ),
    'https://sandbox.monnify.com/api/v2/disbursements/single' => Http::response(
      [],
      500
    )
  ]);

  actingAs($admin)
    ->post(route('managers.withdrawals.pay', [$withdrawal]))
    ->assertOk()
    ->assertJson([
      'message' => '1 processed: 0 successful, 0 pending, 0 failed.'
    ]);

  expect($payout->refresh()->attempt_count)
    ->toBe(1)
    ->and($payout->is_processing)
    ->toBeTrue();

  Http::assertNotSent(
    fn($request) => $request->method() === 'POST' &&
      $request->url() ===
        'https://sandbox.monnify.com/api/v2/disbursements/single'
  );
});
