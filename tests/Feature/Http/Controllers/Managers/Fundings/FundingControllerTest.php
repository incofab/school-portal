<?php

use App\Models\Funding;
use App\Models\InstitutionGroup;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

/**
 * ./vendor/bin/pest --filter FundingControllerTest
 */

beforeEach(function () {
  $this->user = User::factory()
    ->adminManager()
    ->create();
  $this->institutionGroup = InstitutionGroup::factory()->create([
    'credit_wallet' => 1000
  ]);
});

it('creates funding and updates wallet balance', function () {
  $payload = [
    'institution_group_id' => $this->institutionGroup->id,
    'amount' => 500,
    'remark' => 'Funding wallet',
    'reference' => 'REF12345'
  ];

  actingAs($this->user)
    ->postJson(route('managers.funding.store'), $payload)
    ->assertOk();

  // Assert database updates
  assertDatabaseHas('institution_groups', [
    'id' => $this->institutionGroup->id,
    // 'wallet_balance' => 1500,
    'credit_wallet' => 1500
  ]);

  assertDatabaseHas('fundings', [
    'institution_group_id' => $this->institutionGroup->id,
    'amount' => 500,
    'remark' => 'Funding wallet',
    // 'reference' => 'REF12345',
    'funded_by_user_id' => $this->user->id,
    'previous_balance' => 1000,
    'new_balance' => 1500
  ]);

  assertDatabaseHas('transactions', [
    'institution_group_id' => $this->institutionGroup->id,
    'amount' => 500,
    'bbt' => 1000,
    'bat' => 1500
    // 'reference' => 'funding-REF12345'
  ]);
});

it('fails validation with invalid data', function () {
  $payload = [
    'institution_group_id' => $this->institutionGroup->id,
    'amount' => 'invalid_amount', // Invalid numeric value
    'reference' => null // Missing required value
  ];

  actingAs($this->user)
    ->postJson(route('managers.funding.store'), $payload)
    ->assertJsonValidationErrors(['amount', 'reference']);
});
