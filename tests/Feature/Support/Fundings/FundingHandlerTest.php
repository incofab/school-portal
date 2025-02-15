<?php

// use PEST;
// use Mockery;
use App\Models\User;
use App\Enums\WalletType;
use App\Models\Institution;
use App\Enums\TransactionType;
use App\Models\InstitutionUser;
use App\Models\InstitutionGroup;
use App\Models\PaymentReference;
use Illuminate\Database\Eloquent\Model;
use App\Support\Fundings\FundingHandler;

beforeEach(function () {
  // Mock User, InstitutionGroup, and PaymentReference for testing
  $this->institution = Institution::factory()->create(); // Same for Institution
  $this->institutionUser = InstitutionUser::factory()->withInstitution($this->institution)->create();
  $this->user = $this->institution->createdBy;
  // $this->user = $this->institutionUser->user;

  $this->paymentReference = PaymentReference::factory()->withInstitution($this->institution)->create();
  $this->institutionGroup->$this->institution->institutionGroup;
});

it('can create an instance from payment reference', function () {
  $handler = FundingHandler::makeFromPaymentRef($this->paymentReference);

  expect($handler)->toBeInstanceOf(FundingHandler::class);
  expect($handler->institutionGroup)->toEqual($this->institutionGroup);
  expect($handler->user)->toEqual($this->user);
  expect($handler->data['amount'])->toEqual(1500);
  expect($handler->data['reference'])->toEqual('ref123');
});

it('can process loan correctly', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 1000,
    'reference' => 'ref123',
    'remark' => 'Loan remark',
  ]);

  // Mock the method calls
  $this->institutionGroup->shouldReceive('fundDebtWallet')->once();
  $this->institutionGroup->shouldReceive('fundCreditWallet')->once();

  $handler->run(WalletType::Debt);
  $handler->run(WalletType::Credit);
});

it('correctly handles paying debt', function () {
  // Set initial debt balance
  $this->institutionGroup->shouldReceive('debt_wallet')->andReturn(1000);
  $this->institutionGroup->shouldReceive('credit_wallet')->andReturn(500);

  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 1500,
    'reference' => 'ref123',
    'remark' => 'Debt payment remark',
  ]);

  $handler->run(WalletType::Credit);

  // Expectation: payDebt method reduces debt and funds credit wallet
  $this->institutionGroup->shouldHaveReceived('fundDebtWallet')->once();
  $this->institutionGroup->shouldHaveReceived('fundCreditWallet')->once();
});

it('can correctly fund the credit wallet', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 2000,
    'reference' => 'ref123',
    'remark' => 'Credit funding',
  ]);

  // Mock the calls that should happen when funding the credit wallet
  $this->institutionGroup->shouldReceive('fundCreditWallet')->once();

  $handler->fundCreditWallet(2000, null);

  $this->institutionGroup->shouldHaveReceived('fill')->once()->with(['credit_wallet' => 3000]);
  $this->institutionGroup->shouldHaveReceived('save')->once();
});

it('can correctly fund the debt wallet', function () {
  $handler = new FundingHandler($this->institutionGroup, $this->user, [
    'amount' => 1000,
    'reference' => 'ref123',
    'remark' => 'Debt funding',
  ]);

  // Mock the calls that should happen when funding the debt wallet
  $this->institutionGroup->shouldReceive('fundDebtWallet')->once();

  $handler->fundDebtWallet(1000, TransactionType::Credit);

  $this->institutionGroup->shouldHaveReceived('fill')->once()->with(['debt_wallet' => 2000]);
  $this->institutionGroup->shouldHaveReceived('save')->once();
});