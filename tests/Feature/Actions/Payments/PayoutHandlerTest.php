<?php

use App\Actions\Payments\PayoutHandler;
use App\Enums\PayoutMerchantType;
use App\Enums\PayoutStatus;
use App\Enums\WithdrawalStatus;
use App\Models\BankAccount;
use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\Payroll;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Http;

function payoutWithdrawal(
  string $reference,
  float $amount = 5000,
  bool $institutionGroup = false
): Withdrawal {
  $withdrawable = $institutionGroup
    ? InstitutionGroup::factory()->create()
    : Partner::factory()->create();
  $bankAccount = BankAccount::factory()
    ->accountable($withdrawable)
    ->create([
      'bank_code' => '058',
      'account_number' => fake()
        ->unique()
        ->numerify('##########'),
      'account_name' => 'Ada Lovelace'
    ]);

  return Withdrawal::query()->create([
    'bank_account_id' => $bankAccount->id,
    'withdrawable_type' => $withdrawable->getMorphClass(),
    'withdrawable_id' => $withdrawable->id,
    'amount' => $amount,
    'status' => WithdrawalStatus::Pending->value,
    'reference' => $reference
  ]);
}

it(
  'switches withdrawal payouts to paystack without domain recipient handling',
  function () {
    config(['services.payout.merchant' => PayoutMerchantType::Paystack->value]);

    $admin = User::factory()
      ->adminManager()
      ->create();
    $withdrawal = payoutWithdrawal('paystack-single');

    Http::fake([
      'https://api.paystack.co/transferrecipient' => Http::response([
        'status' => true,
        'message' => 'Transfer recipient created successfully',
        'data' => ['recipient_code' => 'RCP_test']
      ]),
      'https://api.paystack.co/transfer' => Http::response([
        'status' => true,
        'message' => 'Transfer has been queued',
        'data' => [
          'reference' => "edumanager-withdrawal-{$withdrawal->id}",
          'transfer_code' => 'TRF_test',
          'status' => 'success'
        ]
      ])
    ]);

    $summary = PayoutHandler::make()->processWithdrawal($withdrawal, $admin);

    $payout = $withdrawal->refresh()->payout;

    expect($summary->successful)
      ->toBe(1)
      ->and($withdrawal->status)
      ->toBe(WithdrawalStatus::Paid)
      ->and($payout->merchant)
      ->toBe(PayoutMerchantType::Paystack)
      ->and($payout->provider_reference)
      ->toBe('TRF_test')
      ->and($payout->status)
      ->toBe(PayoutStatus::Successful);

    Http::assertSent(
      fn($request) => $request->url() ===
        'https://api.paystack.co/transferrecipient' &&
        $request['account_number'] === $withdrawal->bankAccount->account_number
    );
  }
);

it('applies partial results from native paystack bulk payouts', function () {
  config(['services.payout.merchant' => PayoutMerchantType::Paystack->value]);

  $admin = User::factory()
    ->adminManager()
    ->create();
  $successfulWithdrawal = payoutWithdrawal('bulk-success', 4000, true);
  $failedWithdrawal = payoutWithdrawal('bulk-failed', 4500, true);

  Http::fake([
    'https://api.paystack.co/transferrecipient/bulk' => Http::response([
      'status' => true,
      'message' => 'Recipients added successfully',
      'data' => [
        'success' => [
          ['recipient_code' => 'RCP_one'],
          ['recipient_code' => 'RCP_two']
        ],
        'errors' => []
      ]
    ]),
    'https://api.paystack.co/transfer/bulk' => Http::response([
      'status' => true,
      'message' => '2 transfers queued.',
      'data' => [
        [
          'reference' => "edumanager-withdrawal-{$successfulWithdrawal->id}",
          'transfer_code' => 'TRF_one',
          'status' => 'success'
        ],
        [
          'reference' => "edumanager-withdrawal-{$failedWithdrawal->id}",
          'transfer_code' => 'TRF_two',
          'status' => 'failed',
          'gateway_response' => 'Beneficiary bank rejected transfer'
        ]
      ]
    ])
  ]);

  $summary = PayoutHandler::make()->processPendingWithdrawals($admin);

  expect($summary->successful)
    ->toBe(1)
    ->and($summary->failed)
    ->toBe(1)
    ->and($successfulWithdrawal->refresh()->status)
    ->toBe(WithdrawalStatus::Paid)
    ->and($failedWithdrawal->refresh()->status)
    ->toBe(WithdrawalStatus::Declined)
    ->and($successfulWithdrawal->payout->batch_reference)
    ->not->toBeNull()
    ->and($failedWithdrawal->payout->status)
    ->toBe(PayoutStatus::Failed);

  Http::assertSent(
    fn($request) => $request->url() ===
      'https://api.paystack.co/transfer/bulk' &&
      count($request['transfers']) === 2
  );
});

it(
  'supports payroll payouts through the same payout infrastructure',
  function () {
    config(['services.payout.merchant' => PayoutMerchantType::Paystack->value]);

    $admin = User::factory()
      ->adminManager()
      ->create();
    $payroll = Payroll::factory()->create(['net_salary' => 125000]);
    $bankAccount = BankAccount::factory()->create([
      'bank_code' => '058',
      'account_number' => '0123456789',
      'account_name' => 'Grace Hopper'
    ]);

    Http::fake([
      'https://api.paystack.co/transferrecipient/bulk' => Http::response([
        'status' => true,
        'message' => 'Recipients added successfully',
        'data' => [
          'success' => [['recipient_code' => 'RCP_payroll']],
          'errors' => []
        ]
      ]),
      'https://api.paystack.co/transfer/bulk' => Http::response([
        'status' => true,
        'message' => '1 transfer queued.',
        'data' => [
          [
            'reference' => "edumanager-payroll-{$payroll->id}",
            'transfer_code' => 'TRF_payroll',
            'status' => 'success'
          ]
        ]
      ])
    ]);

    $summary = PayoutHandler::make()->processPayrollBulk(
      [['payroll' => $payroll, 'bankAccount' => $bankAccount]],
      $admin
    );

    $payout = $payroll->refresh()->payout;

    expect($summary->successful)
      ->toBe(1)
      ->and($payout)
      ->not->toBeNull()
      ->and($payout->purpose->value)
      ->toBe('payroll')
      ->and($payout->amount)
      ->toBe(125000.0)
      ->and($payout->status)
      ->toBe(PayoutStatus::Successful);
  }
);
