<?php

use App\Enums\PaymentInterval;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\PaymentReference;
use App\Models\Receipt;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\assertDatabaseHas;

function publicFeeForStudent(
  Institution $institution,
  Student $student,
  string $title
): Fee {
  $fee = Fee::factory()
    ->institution($institution)
    ->create([
      'title' => $title,
      'payment_interval' => PaymentInterval::OneTime->value,
      'term' => null,
      'academic_session_id' => null,
      'amount' => 50000
    ]);

  FeeCategory::factory()
    ->fee($fee)
    ->feeable($student)
    ->create();

  return $fee;
}

it(
  'allows a guest to view a student payment link with payment status',
  function () {
    $institution = Institution::factory()->create();
    $student = Student::factory()
      ->withInstitution($institution)
      ->create();
    $partialFee = publicFeeForStudent($institution, $student, 'Tuition');
    $paidFee = publicFeeForStudent($institution, $student, 'Technology');

    Receipt::factory()
      ->fee($partialFee)
      ->student($student)
      ->create([
        'amount' => $partialFee->amount,
        'amount_paid' => 20000,
        'amount_remaining' => 30000
      ]);
    Receipt::factory()
      ->fee($paidFee)
      ->student($student)
      ->create([
        'amount' => $paidFee->amount,
        'amount_paid' => $paidFee->amount,
        'amount_remaining' => 0
      ]);

    $response = $this->get(
      route('public.student-fee-payment', [$institution->code, $student->code])
    );

    $response->assertOk()->assertInertia(
      fn($page) => $page
        ->component('public/student-fee-payment')
        ->has('fees', 2)
        ->where('fees', function ($fees) use ($partialFee, $paidFee) {
          $feesById = collect($fees)->keyBy('id');

          return $feesById[$partialFee->id]['status'] === 'partial' &&
            (float) $feesById[$partialFee->id]['amount_remaining'] ===
              30000.0 &&
            $feesById[$paidFee->id]['status'] === 'paid';
        })
    );
  }
);

it('does not expose a student payment link across institutions', function () {
  $institution = Institution::factory()->create();
  $otherInstitution = Institution::factory()->create();
  $student = Student::factory()
    ->withInstitution($otherInstitution)
    ->create();

  $this->get(
    route('public.student-fee-payment', [$institution->code, $student->code])
  )->assertNotFound();
});

it(
  'records a public manual payment for the selected outstanding fee',
  function () {
    $institution = Institution::factory()->create();
    $student = Student::factory()
      ->withInstitution($institution)
      ->create();
    $fee = publicFeeForStudent($institution, $student, 'School fees');

    $response = $this->postJson(
      route('public.student-fee-payment.store', [
        $institution->code,
        $student->code
      ]),
      [
        'fee_id' => $fee->id,
        'amount' => 25000,
        'merchant' => PaymentMerchantType::Manual->value
      ]
    );

    $response->assertOk()->assertJson(['ok' => true]);

    $manualPayment = ManualPayment::query()->firstOrFail();
    expect($manualPayment->amount)->toBe(25000.0);
    expect($manualPayment->user_id)->toBe($student->user_id);
    expect($manualPayment->paymentable_id)->toBe($fee->id);
    expect($manualPayment->purpose)->toBe(PaymentPurpose::Fee);
    expect($response->json('redirect_url'))->toBe(
      route('institutions.manual-payments.show', [
        $institution->uuid,
        $manualPayment->reference
      ])
    );
    assertDatabaseHas('manual_payments', [
      'institution_id' => $institution->id,
      'paymentable_id' => $fee->id,
      'amount' => 25000
    ]);
  }
);

it(
  'initializes a public automated payment and returns to the payment link',
  function () {
    $institution = Institution::factory()->create();
    $student = Student::factory()
      ->withInstitution($institution)
      ->create();
    $fee = publicFeeForStudent($institution, $student, 'School fees');

    $response = $this->postJson(
      route('public.student-fee-payment.store', [
        $institution->code,
        $student->code
      ]),
      [
        'fee_id' => $fee->id,
        'amount' => $fee->amount,
        'merchant' => PaymentMerchantType::Monnify->value
      ]
    );

    $response->assertOk();
    expect($response->json('authorization_url'))->toContain('monnify/checkout');

    $paymentReference = PaymentReference::query()->firstOrFail();
    expect($paymentReference->redirect_url)->toBe(
      route('public.student-fee-payment', [$institution->code, $student->code])
    );
    expect($paymentReference->amount)->toBe(50000.0);
  }
);

it('rejects public payments above the outstanding balance', function () {
  $institution = Institution::factory()->create();
  $student = Student::factory()
    ->withInstitution($institution)
    ->create();
  $fee = publicFeeForStudent($institution, $student, 'School fees');

  Receipt::factory()
    ->fee($fee)
    ->student($student)
    ->create([
      'amount' => $fee->amount,
      'amount_paid' => 40000,
      'amount_remaining' => 10000
    ]);

  $this->postJson(
    route('public.student-fee-payment.store', [
        $institution->code,
      $student->code
    ]),
    [
      'fee_id' => $fee->id,
      'amount' => 10001,
      'merchant' => PaymentMerchantType::Manual->value
    ]
  )
    ->assertStatus(422)
    ->assertJsonValidationErrors('amount');

  expect(DB::table('manual_payments')->count())->toBe(0);
});
