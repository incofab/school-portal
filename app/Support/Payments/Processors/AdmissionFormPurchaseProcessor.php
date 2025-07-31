<?php
namespace App\Support\Payments\Processors;

use App\Enums\Payments\PaymentStatus;
use App\Models\AdmissionApplication;
use App\Models\AdmissionFormPurchase;
use App\Support\Res;
use App\Support\TransactionHandler;
use DB;

class AdmissionFormPurchaseProcessor extends PaymentProcessor
{
  function processPayment(): Res
  {
    if ($this->paymentReference->status == PaymentStatus::Confirmed) {
      return failRes('Payment already resolved');
    }

    $this->verify();

    /** @var \App\Models\User $user */
    $user = $this->paymentReference->user;
    /** @var \App\Models\AdmissionForm $admissionForm */
    $admissionForm = $this->paymentReference->payable;
    $admissionApplicationId =
      $this->paymentReference->meta['admission_application_id'];

    DB::beginTransaction();
    $this->paymentMerchant->completePayment($this->paymentReference);

    $admissionFormPurchase = AdmissionFormPurchase::query()->firstOrCreate(
      ['reference' => $this->paymentReference->reference],
      [
        'institution_id' => $admissionForm->institution_id,
        'admission_form_id' => $admissionForm->id
      ]
    );

    $admissionApplication = AdmissionApplication::query()->find(
      $admissionApplicationId
    );
    $admissionApplication?->update([
      'admission_form_purchase_id' => $admissionFormPurchase->id
    ]);

    TransactionHandler::makeFromPaymentReference(
      $this->paymentReference
    )->topupCreditWallet(
      $this->paymentReference->amount,
      $admissionApplication ?? $admissionForm,
      "Purchased Admission Form: {$admissionForm->title}"
    );

    DB::commit();

    return successRes('Admission form purchased successfully');
  }
}
