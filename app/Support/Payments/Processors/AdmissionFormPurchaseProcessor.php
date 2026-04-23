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

        DB::beginTransaction();
        $this->paymentMerchant->completePayment(
            $this->paymentReference,
            $this->confirmingUser
        );

        $admissionFormPurchase = AdmissionFormPurchase::query()->firstOrCreate(
            ['reference' => $this->paymentReference->getReference()],
            [
                'institution_id' => $admissionForm->institution_id,
                'admission_form_id' => $admissionForm->id,
            ]
        );

        $admissionApplication = AdmissionApplication::query()->find(
            $admissionApplicationId
        );
        $admissionApplication?->update([
            'admission_form_purchase_id' => $admissionFormPurchase->id,
        ]);

        TransactionHandler::make(
            $this->paymentReference->getInstitution(),
            $this->paymentReference->getReference()
        )->topupCreditWallet(
            $this->paymentReference->getAmount(),
            $admissionApplication ?? $admissionForm,
            "Purchased Admission Form: {$admissionForm->title}"
        );

        DB::commit();

        return successRes('Admission form purchased successfully');
    }
}
