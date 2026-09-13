<?php

use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Receipt;
use App\Models\Student;
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

test('index filters receipts by class, status, and receipt date', function () {
  $classification = Classification::factory()
    ->withInstitution($this->institution)
    ->create();
  $student = Student::factory()
    ->withInstitution($this->institution, $classification)
    ->create();
  $receipt = Receipt::factory()
    ->fee($this->fee)
    ->student($student)
    ->create([
      'status' => 'paid',
      'amount' => 500,
      'created_at' => '2024-06-04 09:00:00'
    ]);
  $largerReceipt = Receipt::factory()
    ->fee($this->fee)
    ->student($student)
    ->create([
      'status' => 'paid',
      'amount' => 1000,
      'created_at' => '2024-06-05 10:00:00'
    ]);

  getJson(
    route('institutions.receipts.index', [
      'institution' => $this->institution,
      'classification' => $classification->id,
      'status' => 'paid',
      'created_at' => [
        'date_from' => '2024-06-04',
        'date_to' => '2024-06-04'
      ]
    ])
  )
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page
        ->where('receipts.data.0.id', $receipt->id)
        ->where('receipts.total', 1)
    );

  getJson(
    route('institutions.receipts.index', [
      'institution' => $this->institution,
      'classification' => $classification->id,
      'status' => 'paid',
      'sortKey' => 'amount',
      'sortDir' => 'desc'
    ])
  )
    ->assertOk()
    ->assertInertia(
      fn(AssertableInertia $page) => $page->where(
        'receipts.data.0.id',
        $largerReceipt->id
      )
    );
});
