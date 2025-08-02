<?php

use App\Actions\Payments\FeePaymentHandler;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\GuardianStudent;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\Institution;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->institutionGroup = $this->institution->institutionGroup;
  $this->admin = $this->institution->createdBy;
  $this->student = Student::factory()
    ->withInstitution($this->institution)
    ->create();
  $this->receipt = Receipt::factory()
    ->institution($this->institution)
    ->student($this->student)
    ->create();
});

it('can load the index page with fee payments and receipt', function () {
  FeePayment::factory(3)->receipt($this->receipt);
  actingAs($this->admin)
    ->get(
      route('institutions.students.fee-payments.index', [
        'institution' => $this->institution->uuid,
        'student' => $this->student->id,
        'receipt' => $this->receipt
      ])
    )
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/students/payments/list-student-fee-payments')
        ->has('receipt')
        ->has('feePayments.data')
    );
});

it('can load the index page for receipts', function () {
  actingAs($this->admin)
    ->get(
      route('institutions.students.receipts.index', [
        'institution' => $this->institution->uuid,
        'student' => $this->student->id
      ])
    )
    ->assertInertia(
      fn($page) => $page
        ->component('institutions/students/payments/list-student-receipts')
        ->has('receipts.data')
    );
});

test(
  'fee payment store creates payment reference and initializes paystack',
  function () {
    Http::fake([
      // '*' => Http::response(
      'https://api.paystack.co/transaction/initialize' => Http::response(
        [
          'status' => true,
          'data' => [
            'authorization_url' => 'authorization_url',
            'reference' => 'reference',
            'access_code' => 'data.access_code',
            'result' => []
          ]
        ],
        200
      )
    ]);

    $fee = Fee::factory()
      ->institution($this->institution)
      ->feeCategories()
      ->create();

    actingAs($this->student->user)
      ->postJson(
        route('institutions.students.fee-payments.store', [
          $this->institution->uuid,
          $this->student
        ]),
        [
          'fee_id' => $fee->id,
          'academic_session_id' => $this->receipt->academic_session_id,
          'term' => $this->receipt->term?->value
        ]
      )
      ->assertOk()
      ->assertJson([
        'success' => true,
        'authorization_url' => 'authorization_url'
      ]);

    $this->assertDatabaseHas('payment_references', [
      'institution_id' => $this->institution->id,
      'user_id' => $this->student->user_id,
      'payable_id' => $this->student->user_id,
      'paymentable_id' => $fee->id,
      'amount' => $fee->amount,
      'purpose' => PaymentPurpose::Fee->value
    ]);
  }
);

test(
  'fee payment store pays for fees when merchant is user-wallet',
  function () {
    $fee = Fee::factory()
      ->institution($this->institution)
      ->feeCategories()
      ->create();
    $guardian = GuardianStudent::factory()
      ->withInstitution($this->institution)
      ->student($this->student)
      ->create();
    $guardianUser = $guardian->guardian;
    $guardianUser->fill(['wallet' => $fee->amount])->save();

    actingAs($guardianUser)
      ->postJson(
        route('institutions.students.fee-payments.store', [
          $this->institution->uuid,
          $this->student
        ]),
        [
          'fee_id' => $fee->id,
          'academic_session_id' => $fee->academic_session_id,
          'term' => $fee->term?->value,
          'merchant' => PaymentMerchantType::UserWallet->value
        ]
      )
      ->assertOk()
      ->assertJson(['success' => true]);

    $receipt = FeePaymentHandler::getReceipt($fee, $this->student->user);
    expect($guardianUser->fresh())->wallet->toBe(floatval(0));
    expect($receipt->fresh())->amount_paid->toBe($fee->amount);
    expect($receipt->fresh()->hasPaid())->toBe(true);
    assertDatabaseHas('fee_payments', [
      'receipt_id' => $receipt->id,
      'payable_id' => $this->student->user->id,
      'payable_type' => $this->student->user->getMorphClass(),
      'fee_id' => $fee->id,
      'amount' => $fee->amount
    ]);
    expect(
      $this->institutionGroup->fresh()->credit_wallet >= $fee->amount
    )->toBe(true);
    assertDatabaseHas('user_transactions', [
      'entity_type' => $guardianUser->getMorphClass(),
      'entity_id' => $guardianUser->id,
      'amount' => $fee->amount
    ]);
  }
);
