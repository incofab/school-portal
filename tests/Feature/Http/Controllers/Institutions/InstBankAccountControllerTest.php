<?php

use App\Models\BankAccount;
use App\Models\Institution;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\put;
use function Pest\Laravel\delete;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function PHPUnit\Framework\assertEquals;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->user = $this->institution->createdBy;
  $this->institutionGroup = $this->institution->institutionGroup;

  actingAs($this->user);

  // Fake Monnify auth API call
  Http::fake([
    'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
      'requestSuccessful' => true,
      'responseBody' => [
        'accessToken' => 'mock_token'
      ]
    ])
  ]);

  Http::fake([
    'sandbox.monnify.com/api/v1/disbursements/account/validate*' => Http::response(
      [
        'requestSuccessful' => true,
        'responseBody' => [
          'accountNumber' => '1234567890',
          'accountName' => 'Test Account',
          'bankCode' => '8477'
        ]
      ],
      200
    )
  ]);
});

it('shows bank accounts for an institution', function () {
  BankAccount::factory(2)
    ->accountable($this->institutionGroup)
    ->create();
  getJson(
    route('institutions.inst-bank-accounts.index', $this->institution)
  )->assertInertia(
    fn($page) => $page
      ->component('institutions/bank-accounts/list-bank-accounts')
      ->has('bankAccounts', 2)
  );
});

it('shows create bank account page', function () {
  $response = getJson(
    route('institutions.inst-bank-accounts.create', $this->institution)
  );
  $response->assertInertia(
    fn($page) => $page->component(
      'institutions/bank-accounts/create-edit-bank-account'
    )
  );
});

it('shows edit page for a bank account', function () {
  $bankAccount = BankAccount::factory()
    ->accountable($this->institutionGroup)
    ->create();
  getJson(
    route('institutions.inst-bank-accounts.edit', [
      $this->institution,
      $bankAccount
    ])
  )->assertInertia(
    fn($page) => $page
      ->component('institutions/bank-accounts/create-edit-bank-account')
      ->where('bankAccount.id', $bankAccount->id)
  );
});

it('can store a new bank account', function () {
  $data = [
    'bank_name' => 'Test Bank',
    'bank_code' => '001',
    'account_name' => 'Test Account',
    'account_number' => '1234567890',
    'institution_id' => $this->institution->id,
    'is_primary' => true
  ];
  $bankAccount1 = BankAccount::factory()
    ->accountable($this->institutionGroup)
    ->isPrimary(true)
    ->create();
  postJson(
    route('institutions.inst-bank-accounts.store', $this->institution),
    $data
  )->assertOk();
  $this->assertDatabaseHas('bank_accounts', [
    'bank_name' => 'Test Bank',
    'account_name' => 'Test Account',
    'account_number' => '1234567890',
    'accountable_id' => $this->institutionGroup->id,
    'accountable_type' => $this->institutionGroup->getMorphClass()
  ]);

  assertEquals(
    1,
    $this->institutionGroup
      ->bankAccounts()
      ->where('is_primary', true)
      ->count()
  );
  expect($bankAccount1->fresh())->is_primary->toBe(false);
});

it('can update an existing bank account', function () {
  [$bankAccount, $bankAccount2] = BankAccount::factory(2)
    ->accountable($this->institutionGroup)
    ->create();
  $bankAccount2->update(['is_primary' => true]);

  $data = [
    'bank_name' => 'Updated Bank',
    'bank_code' => '002',
    'account_name' => 'Updated Name',
    'account_number' => '0987654321',
    'institution_id' => $this->institution->id,
    'is_primary' => true
  ];

  $response = put(
    route('institutions.inst-bank-accounts.update', [
      $this->institution,
      $bankAccount
    ]),
    $data
  );

  $response->assertOk();
  assertDatabaseHas('bank_accounts', [
    'id' => $bankAccount->id,
    'bank_name' => 'Updated Bank',
    'account_name' => 'Updated Name',
    'account_number' => '0987654321',
    'is_primary' => true
  ]);
  expect($bankAccount2->fresh())->is_primary->toBe(false);
});

it('can delete a bank account', function () {
  $bankAccount = BankAccount::factory()
    ->accountable($this->institutionGroup)
    ->create();

  delete(
    route('institutions.inst-bank-accounts.destroy', [
      $this->institution,
      $bankAccount
    ])
  )->assertOk();
  assertSoftDeleted('bank_accounts', [
    'id' => $bankAccount->id
  ]);
});
