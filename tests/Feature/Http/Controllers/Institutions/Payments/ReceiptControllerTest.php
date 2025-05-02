<?php

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function PHPUnit\Framework\assertTrue;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->user = User::factory()
    ->admin($this->institution)
    ->create();
  $this->fee = Fee::factory()
    ->for($this->institution)
    ->create(['amount' => 1000]);
  $this->receipt = Receipt::factory()
    ->institution($this->institution)
    ->create(['amount' => 500]);
  $this->feePayment = FeePayment::factory()
    ->fee($this->fee)
    ->receipt($this->receipt)
    ->create(['amount' => 500]);

  actingAs($this->user);
});

test('index displays list of receipts with totals and relations', function () {
  getJson(route('institutions.receipts.index', [$this->institution]))
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/payments/list-receipts')
        ->has('receipts')
        ->has('receipts.data', 1)
        ->has('receipts.data.0.user')
        ->has('receipts.data.0.user.student') // Check nested relation
        ->has('receipts.data.0.academic_session')
        ->has('receipts.data.0.fee')
        ->has('receipts.data.0.fee_payments_count') // Check count loaded
    );
});

test('show displays a single receipt with its relations', function () {
  getJson(
    route('institutions.receipts.show', [$this->institution, $this->receipt])
  )
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->component('institutions/payments/show-receipt')
        ->has('receipt')
        ->where('receipt.id', $this->receipt->id)
        ->has('receipt.user') // Check relation loaded
    );
});
