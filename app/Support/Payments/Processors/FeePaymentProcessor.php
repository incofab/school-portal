<?php

namespace App\Support\Payments\Processors;

use App\Actions\Payments\FeePaymentHandler;
use App\Enums\Payments\PaymentStatus;
use App\Models\Fee;
use App\Support\Res;
use App\Support\TransactionHandler;
use DB;

class FeePaymentProcessor extends PaymentProcessor
{
    public function processPayment(): Res
    {
        if ($this->paymentReference->getStatus() !== PaymentStatus::Pending) {
            return failRes('Payment already resolved');
        }

        $res = $this->verify();

        if ($res->isNotSuccessful()) {
            return $res;
        }

        $fee = $this->paymentReference->getPaymentable();
        if (! ($fee instanceof Fee)) {
            return failRes('Fee record not found');
        }

        DB::beginTransaction();
        $this->paymentMerchant->completePayment(
            $this->paymentReference,
            $this->confirmingUser
        );
        $user = $this->paymentReference->getPayable();

        FeePaymentHandler::make($this->paymentReference->getInstitution())->create(
            [
                'reference' => $this->paymentReference->getReference(),
                'user_id' => $user->id ?? $this->paymentReference->getUser()?->id,
                'amount' => $this->paymentReference->getAmount(),
                'method' => $this->paymentReference->getPaymentMethod()->value,
            ],
            $fee,
            $this->paymentReference->getPayable(),
            $this->confirmingUser,
            allowOverPayment: true
        );

        TransactionHandler::make(
            $this->paymentReference->getInstitution(),
            $this->paymentReference->getReference()
        )->topupCreditWallet(
            $this->paymentReference->getAmount(),
            $this->paymentReference->getModel(),
            'Fee payment for: '.$fee->title
        );

        DB::commit();

        return successRes('Fee Payment processed successfully');
    }
}
