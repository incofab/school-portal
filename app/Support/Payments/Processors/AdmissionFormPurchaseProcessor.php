<?php
namespace App\Support\Payments\Processors;

use App\Enums\Payments\PaymentStatus;
use App\Enums\TransactionType;
use App\Models\AdmissionApplication;
use App\Models\AdmissionFormPurchase;
use App\Support\Fundings\FundingHandler;
use App\Support\Res;
use DB;

class AdmissionFormPurchaseProcessor extends PaymentProcessor
{
  function handleCallback(): Res
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
    $this->paymentReference->confirmPayment();

    $admissionFormPurchase = AdmissionFormPurchase::query()->firstOrCreate(
      ['reference' => $this->paymentReference->reference],
      [
        'institution_id' => $admissionForm->institution_id,
        'admission_form_id' => $admissionForm->id
      ]
    );

    AdmissionApplication::query()
      ->find($admissionApplicationId)
      ?->update(['admission_form_purchase_id' => $admissionFormPurchase->id]);

    FundingHandler::makeFromPaymentRef(
      $this->paymentReference,
      'Purchased Admission Form: ' . $admissionForm->title
    )->fundCreditWallet(
      $this->paymentReference->amount,
      TransactionType::Credit,
      $admissionForm
    );
    DB::commit();

    return successRes('Admission form purchased successfully');
  }
}
