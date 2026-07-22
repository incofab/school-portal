<?php

use App\Enums\WalletType;
use App\Enums\TransactionType;
use App\Models\Funding;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\PaymentReference;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserTransaction;
use App\Support\Fundings\FundingHandler;
use App\Support\TransactionHandler;
use App\Support\UserTransactionHandler;

use function PHPUnit\Framework\assertEquals;

beforeEach(function () {
  // Mock User, InstitutionGroup, and PaymentReference for testing
  $this->institution = Institution::factory()->create(); // Same for Institution
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->institutionUser = InstitutionUser::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->user = $this->institution->createdBy;
  // $this->user = $this->institutionUser->user;

  $this->paymentReference = PaymentReference::factory()
    ->withInstitution($this->institution)
    ->create();
});

it('can process loan correctly', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 1000,
    'reference' => 'ref123',
    'remark' => 'Loan remark'
  ]);

  $handler->run(WalletType::Credit);
  assertEquals(1000, $this->institutionGroup->fresh()->credit_wallet);
});

it('correctly handles paying debt', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 1500,
    'reference' => 'ref123',
    'remark' => 'Debt payment remark'
  ]);

  $handler->run(WalletType::Debt);
  assertEquals(1500, $this->institutionGroup->fresh()->debt_wallet);
});

it('records one institution ledger row for duplicate references', function () {
  $reference = 'duplicate-institution-ledger-reference';

  TransactionHandler::make(
    $this->institutionGroup,
    $reference
  )->topupCreditWallet(1000, $this->paymentReference, 'First attempt');

  TransactionHandler::make(
    $this->institutionGroup->fresh(),
    $reference
  )->topupCreditWallet(1000, $this->paymentReference, 'Duplicate attempt');

  expect($this->institutionGroup->fresh()->credit_wallet)->toBe(1000.0);
  expect(
    Transaction::query()
      ->where('reference', $reference)
      ->count()
  )->toBe(1);
});

it(
  'records one funding and ledger row for duplicate funding references',
  function () {
    $reference = 'duplicate-funding-reference';

    $recorder = \App\Support\Fundings\RecordFunding::make(
      $this->institutionGroup,
      $this->user
    );
    $recorder->recordCreditTopup(1000, $reference, null, 'First attempt');
    $recorder->recordCreditTopup(1000, $reference, null, 'Duplicate attempt');

    expect($this->institutionGroup->fresh()->credit_wallet)->toBe(1000.0);
    expect(
      Funding::query()
        ->where('reference', $reference)
        ->count()
    )->toBe(1);
    expect(
      Transaction::query()
        ->where('reference', $reference)
        ->count()
    )->toBe(1);
  }
);

it(
  'prevents a second wallet debit from overdrawing the same user',
  function () {
    $user = User::factory()->create(['wallet' => 100]);

    UserTransactionHandler::recordTransaction(
      amount: 80,
      entity: $user,
      transactionType: TransactionType::Debit,
      transactionable: $this->paymentReference,
      reference: 'wallet-debit-one'
    );

    expect(
      fn() => UserTransactionHandler::recordTransaction(
        amount: 80,
        entity: $user->fresh(),
        transactionType: TransactionType::Debit,
        transactionable: $this->paymentReference,
        reference: 'wallet-debit-two'
      )
    )->toThrow(Exception::class, 'User wallet cannot be zero or less');

    expect($user->fresh()->wallet)->toBe(20.0);
    expect(UserTransaction::query()->count())->toBe(1);
  }
);

it('records one user ledger row for duplicate references', function () {
  $user = User::factory()->create(['wallet' => 0]);
  $reference = 'duplicate-user-ledger-reference';

  UserTransactionHandler::recordTransaction(
    amount: 1000,
    entity: $user,
    transactionType: TransactionType::Credit,
    transactionable: $this->paymentReference,
    reference: $reference
  );
  UserTransactionHandler::recordTransaction(
    amount: 1000,
    entity: $user->fresh(),
    transactionType: TransactionType::Credit,
    transactionable: $this->paymentReference,
    reference: $reference
  );

  expect($user->fresh()->wallet)->toBe(1000.0);
  expect(
    UserTransaction::query()
      ->where('reference', $reference)
      ->count()
  )->toBe(1);
});

/*
it('can correctly fund the credit wallet', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 2000,
    'reference' => 'ref123',
    'remark' => 'Credit funding'
  ]);

  $handler->fundCreditWallet(2000, TransactionType::Credit, null);
  assertEquals($this->institutionGroup->credit_wallet, 2000);
});

it('can correctly fund the debt wallet', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 1000,
    'reference' => 'ref123',
    'remark' => 'Debt funding'
  ]);

  $handler->fundDebtWallet(1000, TransactionType::Credit);

  assertEquals($this->institutionGroup->debt_wallet, 1000);
});
*/
