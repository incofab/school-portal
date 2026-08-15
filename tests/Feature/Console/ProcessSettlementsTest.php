<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\SettlementStatus;
use App\Enums\WithdrawalStatus;
use App\Models\BankAccount;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Models\Settlement;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

function settlementPayment(
  Institution $institution,
  float $amount,
  array $overrides = []
): PaymentReference {
  return PaymentReference::factory()
    ->payable($institution->institutionGroup)
    ->create([
      'institution_id' => $institution->id,
      'user_id' => $institution->createdBy->id,
      'amount' => $amount,
      'status' => PaymentStatus::Confirmed,
      'merchant' => PaymentMerchantType::Monnify,
      'purpose' => PaymentPurpose::Fee,
      'processed_at' => now(),
      ...$overrides
    ]);
}

function prepareSettlementInstitution(float $wallet = 100000): Institution
{
  $institution = Institution::factory()->create();
  $institution->institutionGroup
    ->forceFill(['credit_wallet' => $wallet])
    ->save();

  BankAccount::factory()
    ->accountable($institution->institutionGroup)
    ->create();

  return $institution->refresh();
}

it(
  'creates settlements and withdrawals for eligible unsettled payments',
  function () {
    $institution = prepareSettlementInstitution();

    $paymentOne = settlementPayment($institution, 1200);
    $paymentTwo = settlementPayment($institution, 800);

    Artisan::call('settlements:process');

    $settlement = Settlement::query()->first();

    expect($settlement)
      ->not->toBeNull()
      ->and($settlement->institution_id)
      ->toBe($institution->id)
      ->and($settlement->amount)
      ->toBe(2000.0)
      ->and($settlement->status)
      ->toBe(SettlementStatus::Completed)
      ->and($settlement->withdrawal_id)
      ->not->toBeNull()
      ->and(
        $settlement
          ->paymentReferences()
          ->pluck('payment_references.id')
          ->all()
      )
      ->toEqualCanonicalizing([$paymentOne->id, $paymentTwo->id]);

    expect($paymentOne->refresh()->settled_at)
      ->not->toBeNull()
      ->and($paymentTwo->refresh()->settled_at)
      ->not->toBeNull();

    $withdrawal = Withdrawal::query()->find($settlement->withdrawal_id);

    expect($withdrawal)
      ->not->toBeNull()
      ->and($withdrawal->amount)
      ->toBe(2000.0)
      ->and($withdrawal->status)
      ->toBe(WithdrawalStatus::Pending)
      ->and($withdrawal->withdrawable_id)
      ->toBe($institution->institution_group_id);
  }
);

it('is idempotent when run repeatedly', function () {
  $institution = prepareSettlementInstitution();

  settlementPayment($institution, 1500);

  Artisan::call('settlements:process');
  Artisan::call('settlements:process');

  expect(Settlement::query()->count())
    ->toBe(1)
    ->and(Withdrawal::query()->count())
    ->toBe(1)
    ->and(DB::table('settlement_payments')->count())
    ->toBe(1);
});

it('skips ineligible and manually settled payments', function () {
  $institution = prepareSettlementInstitution();

  $eligible = settlementPayment($institution, 1000);
  settlementPayment($institution, 700, [
    'status' => PaymentStatus::Pending
  ]);
  settlementPayment($institution, 800, [
    'merchant' => PaymentMerchantType::Manual
  ]);
  settlementPayment($institution, 900, [
    'purpose' => PaymentPurpose::WalletFunding
  ]);
  settlementPayment($institution, 1100, [
    'settled_at' => now()
  ]);

  Artisan::call('settlements:process');

  $settlement = Settlement::query()->first();

  expect($settlement->amount)
    ->toBe(1000.0)
    ->and(
      $settlement
        ->paymentReferences()
        ->pluck('payment_references.id')
        ->all()
    )
    ->toBe([$eligible->id]);
});

it(
  'processes institutions independently when one settlement fails',
  function () {
    $institutionWithoutBank = Institution::factory()->create();
    $institutionWithoutBank->institutionGroup
      ->forceFill(['credit_wallet' => 100000])
      ->save();
    settlementPayment($institutionWithoutBank, 1200);

    $validInstitution = prepareSettlementInstitution();
    settlementPayment($validInstitution, 1500);

    Artisan::call('settlements:process');

    expect(Settlement::query()->count())
      ->toBe(1)
      ->and(Settlement::query()->first()->institution_id)
      ->toBe($validInstitution->id)
      ->and(Withdrawal::query()->count())
      ->toBe(1)
      ->and(
        PaymentReference::query()
          ->where('institution_id', $institutionWithoutBank->id)
          ->first()->settled_at
      )
      ->toBeNull();
  }
);

it('groups unsettled payments by institution', function () {
  $firstInstitution = prepareSettlementInstitution();
  $secondInstitution = prepareSettlementInstitution();

  settlementPayment($firstInstitution, 1100);
  settlementPayment($firstInstitution, 1300);
  settlementPayment($secondInstitution, 1700);

  Artisan::call('settlements:process');

  expect(Settlement::query()->count())
    ->toBe(2)
    ->and(
      Settlement::query()
        ->where('institution_id', $firstInstitution->id)
        ->value('amount')
    )
    ->toBe(2400.0)
    ->and(
      Settlement::query()
        ->where('institution_id', $secondInstitution->id)
        ->value('amount')
    )
    ->toBe(1700.0);
});
