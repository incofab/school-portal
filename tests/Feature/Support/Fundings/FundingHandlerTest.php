<?php

use App\Enums\WalletType;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\PaymentReference;
use App\Support\Fundings\FundingHandler;

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
  assertEquals($this->institutionGroup->credit_wallet, 1000);
});

it('correctly handles paying debt', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 1500,
    'reference' => 'ref123',
    'remark' => 'Debt payment remark'
  ]);

  $handler->run(WalletType::Debt);
  assertEquals($this->institutionGroup->debt_wallet, 1500);
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
