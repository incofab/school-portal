<?php

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\AdmissionFormPurchase;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

it(
  'does not duplicate side effects for duplicate Paystack callbacks',
  function () {
    $institution = Institution::factory()->create();
    $admissionForm = AdmissionForm::factory()
      ->for($institution)
      ->create(['price' => 1000]);
    $admissionApplication = AdmissionApplication::factory()
      ->admissionForm($admissionForm)
      ->create();

    $paymentReference = PaymentReference::factory()
      ->withInstitution($institution)
      ->payable($admissionForm)
      ->paymentable($admissionApplication)
      ->create([
        'amount' => $admissionForm->price,
        'purpose' => PaymentPurpose::AdmissionFormPurchase,
        'merchant' => PaymentMerchantType::Paystack,
        'meta' => ['admission_application_id' => $admissionApplication->id]
      ]);

    Http::fake([
      'https://api.paystack.co/transaction/verify/*' => Http::response(
        [
          'status' => true,
          'data' => [
            'status' => 'success',
            'amount' => $admissionForm->price * 100,
            'reference' => $paymentReference->reference
          ]
        ],
        200
      )
    ]);

    $this->get(
      route('paystack.callback', ['reference' => $paymentReference->reference])
    )->assertRedirect();
    $this->get(
      route('paystack.callback', ['reference' => $paymentReference->reference])
    )->assertRedirect();

    expect($paymentReference->fresh()->status)->toBe(PaymentStatus::Confirmed);
    expect($paymentReference->fresh()->processed_at)->not->toBeNull();
    expect(
      AdmissionFormPurchase::query()
        ->where('reference', $paymentReference->reference)
        ->count()
    )->toBe(1);
    expect(
      Transaction::query()
        ->where('reference', $paymentReference->reference)
        ->count()
    )->toBe(1);
    expect($institution->institutionGroup->fresh()->credit_wallet)->toBe(
      1000.0
    );
  }
);
