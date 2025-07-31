<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Enums\TransactionType;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\AdmissionFormPurchase;
use App\Models\Funding;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Support\Payments\Processors\AdmissionFormPurchaseProcessor;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
  $this->institution = Institution::factory()->create();
  $this->admin = $this->institution->createdBy;
  $this->admissionForm = AdmissionForm::factory()
    ->for($this->institution)
    ->create(['price' => 1000]);
  $this->admissionApplication = AdmissionApplication::factory()
    ->admissionForm($this->admissionForm)
    ->create();
  $this->paymentReference = PaymentReference::factory()
    ->withInstitution($this->institution)
    ->payable($this->admissionForm)
    ->paymentable($this->admissionApplication)
    ->create([
      'amount' => $this->admissionForm->price,
      'purpose' => PaymentPurpose::AdmissionFormPurchase,
      'merchant' => PaymentMerchantType::Paystack,
      'meta' => ['admission_application_id' => $this->admissionApplication->id]
    ]);
});

it('can handle a successful admission form purchase callback', function () {
  $institutionGroup = $this->institution->institutionGroup;
  $creditWallet = $institutionGroup->credit_wallet;
  // Mock Paystack verification to return success
  Http::fake([
    'https://api.paystack.co/transaction/verify/*' => Http::response(
      [
        'status' => true,
        'data' => [
          'status' => 'success',
          'amount' => $this->admissionForm->price * 100, // Paystack returns amount in kobo
          'reference' => $this->paymentReference->reference
        ]
      ],
      200
    )
  ]);

  // Create the processor
  $processor = AdmissionFormPurchaseProcessor::make($this->paymentReference);

  // Call the processPayment method
  $result = $processor->processPaymentWithTransaction();

  // Assertions
  expect($result->isSuccessful())->toBeTrue();
  expect($result->message)->toBe('Admission form purchased successfully');

  // Check if the payment reference status is updated
  assertDatabaseHas('payment_references', [
    'id' => $this->paymentReference->id,
    'status' => PaymentStatus::Confirmed->value
  ]);

  // Check if the admission form purchase record is created
  assertDatabaseHas('admission_form_purchases', [
    'reference' => $this->paymentReference->reference,
    'admission_form_id' => $this->admissionForm->id,
    'institution_id' => $this->institution->id
  ]);

  // Check if the admission application is updated
  assertDatabaseHas('admission_applications', [
    'id' => $this->admissionApplication->id,
    'admission_form_purchase_id' => AdmissionFormPurchase::where(
      'reference',
      $this->paymentReference->reference
    )->first()->id
  ]);

  // Check if the credit wallet was funded
  // assertDatabaseHas('fundings', [
  //   'reference' => Funding::creditReference($this->paymentReference->reference),
  //   'amount' => $this->paymentReference->amount,
  //   'wallet' => \App\Enums\WalletType::Credit->value,
  //   'fundable_id' => $this->admissionForm->id,
  //   'fundable_type' => $this->admissionForm->getMorphClass()
  // ]);
  assertDatabaseHas('transactions', [
    'reference' => $this->paymentReference->reference,
    'amount' => $this->paymentReference->amount,
    'type' => TransactionType::Credit->value
  ]);
  expect($institutionGroup->fresh()->credit_wallet)->toBe(
    $creditWallet + $this->paymentReference->amount
  );
});

it('fails if payment is already resolved', function () {
  $this->paymentReference->update(['status' => PaymentStatus::Confirmed]);
  $processor = AdmissionFormPurchaseProcessor::make($this->paymentReference);
  $result = $processor->processPaymentWithTransaction();
  // Assertions
  expect($result->isSuccessful())->toBeFalse();
  expect($result->message)->toBe('Payment already resolved');

  assertDatabaseHas('payment_references', [
    'id' => $this->paymentReference->id,
    'status' => PaymentStatus::Confirmed->value
  ]);
  assertDatabaseMissing('admission_form_purchases', [
    'reference' => $this->paymentReference->reference
  ]);
});

it(
  'can handle a successful admission form purchase callback without an application',
  function () {
    // Mock Paystack verification to return success
    Http::fake([
      'https://api.paystack.co/transaction/verify/*' => Http::response(
        [
          'status' => true,
          'data' => [
            'status' => 'success',
            'amount' => $this->admissionForm->price * 100, // Paystack returns amount in kobo
            'reference' => $this->paymentReference->reference
          ]
        ],
        200
      )
    ]);
    $this->paymentReference->update([
      'paymentable_id' => null,
      'paymentable_type' => null,
      'meta' => ['admission_application_id' => null]
    ]);
    // Create the processor
    $processor = AdmissionFormPurchaseProcessor::make($this->paymentReference);

    // Call the processPayment method
    $result = $processor->processPaymentWithTransaction();

    // Assertions
    expect($result->isSuccessful())->toBeTrue();
    expect($result->message)->toBe('Admission form purchased successfully');

    // Check if the payment reference status is updated
    assertDatabaseHas('payment_references', [
      'id' => $this->paymentReference->id,
      'status' => PaymentStatus::Confirmed->value
    ]);

    // Check if the admission form purchase record is created
    assertDatabaseHas('admission_form_purchases', [
      'reference' => $this->paymentReference->reference,
      'admission_form_id' => $this->admissionForm->id,
      'institution_id' => $this->institution->id
    ]);

    // Check if the admission application is not updated
    assertDatabaseMissing('admission_applications', [
      'id' => $this->admissionApplication->id,
      'admission_form_purchase_id' => AdmissionFormPurchase::where(
        'reference',
        $this->paymentReference->reference
      )->first()->id
    ]);

    assertDatabaseHas('transactions', [
      'reference' => $this->paymentReference->reference,
      'amount' => $this->paymentReference->amount,
      'type' => TransactionType::Credit->value
    ]);
  }
);
