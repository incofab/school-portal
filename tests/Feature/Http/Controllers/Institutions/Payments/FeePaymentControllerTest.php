<?php

use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\Receipt;
use App\Models\Student;
use Inertia\Testing\AssertableInertia;
use Illuminate\Support\Str;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertSoftDeleted;

beforeEach(function () {
  // Create a user and assign the admin role
  $this->admin = InstitutionUser::factory()->create([
    'type' => InstitutionUserType::Admin
  ]);
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->fee = Fee::factory()
    ->institution($this->institution)
    ->create(['amount' => 5000]);
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->academicSession = AcademicSession::factory()->create();
});

it('stores a fee payment successfully', function () {
  $reference = Str::uuid();
  $amount = 1000;
  $data = [
    'reference' => $reference,
    'fee_id' => $this->fee->id,
    'user_id' => $this->student->user_id,
    'amount' => $amount,
    'academic_session_id' => $this->academicSession->id,
    'term' => TermType::First->value,
    'method' => 'credit_card'
  ];

  actingAs($this->admin)
    ->postJson(
      route('institutions.fee-payments.store', $this->institution),
      $data
    )
    ->assertOk();

  assertDatabaseHas('fee_payments', [
    'fee_id' => $this->fee->id,
    'amount' => $amount
  ]);
  assertDatabaseHas('receipts', [
    'institution_id' => $this->institution->id,
    'user_id' => $this->student->user_id,
    'amount_paid' => $amount,
    'fee_id' => $this->fee->id
  ]);
  // Second payment
  $data = [...$data, 'reference' => Str::uuid(), 'amount' => $amount];
  actingAs($this->admin)
    ->postJson(
      route('institutions.fee-payments.store', $this->institution),
      $data
    )
    ->assertOk();
  assertDatabaseHas('receipts', [
    'institution_id' => $this->institution->id,
    'user_id' => $this->student->user_id,
    'amount_paid' => $amount * 2,
    'fee_id' => $this->fee->id
  ]);
});

it(
  'filters fee payments by student class, status, method, and payment date',
  function () {
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
        'created_at' => '2024-06-04 09:00:00'
      ]);
    $payment = FeePayment::factory()
      ->fee($this->fee)
      ->receipt($receipt)
      ->create([
        'amount' => 500,
        'method' => 'bank',
        'created_at' => '2024-06-04 09:30:00'
      ]);

    actingAs($this->admin);

    getJson(
      route('institutions.fee-payments.index', [
        'institution' => $this->institution,
        'classification' => $classification->id,
        'status' => 'paid',
        'method' => 'bank',
        'created_at' => [
          'date_from' => '2024-06-04',
          'date_to' => '2024-06-04'
        ]
      ])
    )
      ->assertOk()
      ->assertInertia(
        fn(AssertableInertia $page) => $page
          ->where('feePayments.data.0.id', $payment->id)
          ->where('feePayments.total', 1)
      );

    $largerPayment = FeePayment::factory()
      ->fee($this->fee)
      ->receipt($receipt)
      ->create([
        'amount' => 2000,
        'method' => 'bank',
        'created_at' => '2024-06-04 10:00:00'
      ]);

    getJson(
      route('institutions.fee-payments.index', [
        'institution' => $this->institution,
        'sortKey' => 'amount',
        'sortDir' => 'desc'
      ])
    )
      ->assertOk()
      ->assertInertia(
        fn(AssertableInertia $page) => $page->where(
          'feePayments.data.0.id',
          $largerPayment->id
        )
      );
  }
);

it('updates fee payments', function () {
  [$amountPaid1, $amountPaid2] = [1000, 2000];
  $receipt = Receipt::factory()
    ->institution($this->institution)
    ->for($this->fee)
    ->create([
      'amount' => $this->fee->amount,
      'amount_paid' => $amountPaid1 + $amountPaid2,
      'amount_remaining' => $this->fee->amount - $amountPaid1 - $amountPaid2
    ]);
  $feePayment1 = FeePayment::factory()
    ->fee($this->fee)
    ->receipt($receipt)
    ->create(['amount' => $amountPaid1]);
  $feePayment2 = FeePayment::factory()
    ->fee($this->fee)
    ->receipt($receipt)
    ->create(['amount' => $amountPaid2]);

  actingAs($this->admin)
    ->deleteJson(
      route('institutions.fee-payments.destroy', [
        $this->institution,
        $feePayment1
      ])
    )
    ->assertOk();

  expect($receipt->fresh())
    ->amount_paid->toBe(floatval($amountPaid2))
    ->amount_remaining->toBe(floatval($this->fee->amount - $amountPaid2));

  assertSoftDeleted('fee_payments', ['id' => $feePayment1->id]);
  expect($feePayment2->fresh())
    ->not()
    ->toBeNull();
});
