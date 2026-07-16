<?php

use App\Enums\PriceLists\PaymentStructure;
use App\Enums\PriceLists\PriceType;
use App\Models\InstitutionGroup;
use App\Models\Partner;
use App\Models\PriceList;
use App\Models\User;

use function Pest\Laravel\actingAs;

function billingPayload(
  InstitutionGroup $institutionGroup,
  array $overrides = []
): array {
  return [
    'institution_group_id' => $institutionGroup->id,
    'billable' => PriceType::ResultChecking->value,
    'payment_structure' => PaymentStructure::PerStudentPerTerm->value,
    'amount' => 400,
    ...$overrides
  ];
}

it('creates price lists', function () {
  $admin = User::factory()
    ->adminManager()
    ->create();
  $institutionGroup = InstitutionGroup::factory()->create();

  actingAs($admin)
    ->post(
      route('managers.billings.store'),
      billingPayload($institutionGroup, [
        'partner_commission' => 100
      ])
    )
    ->assertOk();

  $this->assertDatabaseHas('price_lists', [
    'institution_group_id' => $institutionGroup->id,
    'type' => PriceType::ResultChecking->value,
    'amount' => 400,
    'partner_commission' => 100
  ]);
});

it(
  'allows admin managers to set partner commission when editing price lists',
  function () {
    $admin = User::factory()
      ->adminManager()
      ->create();
    $priceList = PriceList::factory()
      ->type(PriceType::ResultChecking)
      ->create([
        'amount' => 400,
        'partner_commission' => 0
      ]);

    actingAs($admin)
      ->post(
        route('managers.billings.store'),
        billingPayload($priceList->institutionGroup, [
          'amount' => 450,
          'partner_commission' => 100
        ])
      )
      ->assertOk();

    $this->assertDatabaseHas('price_lists', [
      'id' => $priceList->id,
      'amount' => 450,
      'partner_commission' => 100
    ]);
  }
);

it(
  'requires price list amount to be greater than partner commission',
  function () {
    $admin = User::factory()
      ->adminManager()
      ->create();
    $priceList = PriceList::factory()
      ->type(PriceType::ResultChecking)
      ->create(['amount' => 400]);

    actingAs($admin)
      ->post(
        route('managers.billings.store'),
        billingPayload($priceList->institutionGroup, [
          'amount' => 100,
          'partner_commission' => 100
        ])
      )
      ->assertStatus(422)
      ->assertJsonValidationErrors('partner_commission');
  }
);

it('prevents partner managers from changing price lists', function () {
  $partner = Partner::factory()->create();
  $institutionGroup = InstitutionGroup::factory()->create();

  actingAs($partner->user)
    ->post(route('managers.billings.store'), billingPayload($institutionGroup))
    ->assertForbidden();
});
