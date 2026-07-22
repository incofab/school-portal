<?php

namespace App\Support\Payments\Processors;

use App\Enums\Payments\PaymentStatus;
use App\Models\AdmissionApplication;
use App\Models\AdmissionFormPurchase;
use App\Support\Audit\AcademicIntegrityActivityLogger;
use App\Support\Audit\ModelAudit;
use App\Support\Res;
use App\Support\TransactionHandler;

class AdmissionFormPurchaseProcessor extends PaymentProcessor
{
  public function processPayment(): Res
  {
    if ($this->paymentReference->getStatus() == PaymentStatus::Confirmed) {
      return failRes('Payment already resolved');
    }

    $res = $this->verify();
    if ($res->isNotSuccessful()) {
      return $res;
    }

    /** @var \App\Models\User $user */
    $user = $this->paymentReference->getUser();
    /** @var \App\Models\AdmissionForm $admissionForm */
    $admissionForm = $this->paymentReference->getPayable();
    $admissionApplicationId = $this->paymentReference->getPaymentMeta()[
      'admission_application_id'
    ];

    $this->paymentMerchant->completePayment(
      $this->paymentReference,
      $this->confirmingUser
    );

    $admissionFormPurchase = ModelAudit::withoutAuditingFor(
      AdmissionFormPurchase::class,
      fn() => AdmissionFormPurchase::query()->firstOrCreate(
        ['reference' => $this->paymentReference->getReference()],
        [
          'institution_id' => $admissionForm->institution_id,
          'admission_form_id' => $admissionForm->id
        ]
      )
    );

    $admissionApplication = AdmissionApplication::query()->find(
      $admissionApplicationId
    );
    ModelAudit::withoutAuditingFor(
      AdmissionApplication::class,
      function () use ($admissionApplication, $admissionFormPurchase) {
        $admissionApplication?->update([
          'admission_form_purchase_id' => $admissionFormPurchase->id
        ]);
      }
    );

    if (!$this->paymentMerchant->isManualPayment()) {
      TransactionHandler::make(
        $this->paymentReference->getInstitution(),
        $this->paymentReference->getReference()
      )->topupCreditWallet(
        $this->paymentReference->getAmount(),
        $admissionApplication ?? $admissionForm,
        "Purchased Admission Form: {$admissionForm->title}"
      );
    }

    app(AcademicIntegrityActivityLogger::class)->admissionFormPurchased(
      $admissionFormPurchase,
      $admissionApplication,
      $admissionForm,
      [
        'amount' => $this->paymentReference->getAmount(),
        'merchant' => $this->paymentReference->getPaymentMerchant(),
        'reference' => $this->paymentReference->getReference()
      ]
    );

    return successRes('Admission form purchased successfully');
  }
}
