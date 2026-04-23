<?php

namespace App\Support\Payments\Merchants;

use App\Contracts\Payments\PaymentRecord;
use App\DTO\PaymentReferenceDto;
use App\Enums\TransactionType;
use App\Models\User;
use App\Support\Res;
use App\Support\UserTransactionHandler;

class PaymentWallet extends PaymentMerchant
{
    public function init(
        PaymentReferenceDto $paymentReferenceDto,
        bool $generateReferenceOnly = false
    ) {
        $paymentReference = $this->createPaymentReference($paymentReferenceDto);
        $ret = successRes('', [
            'reference' => $paymentReference->reference,
            'amount' => $paymentReferenceDto->amount,
        ]);

        return [$ret, $paymentReference];
    }

    public function completePayment(
        PaymentRecord $paymentReference,
        ?User $user = null
    ): void {
        parent::completePayment($paymentReference, $user);

        UserTransactionHandler::recordTransaction(
            amount: $paymentReference->getAmount(),
            entity: $paymentReference->getUser(),
            transactionType: TransactionType::Debit,
            transactionable: $paymentReference->getModel(),
            reference: $paymentReference->getReference()
        );
    }

    public function verify(PaymentRecord $paymentReference): Res
    {
        $user = $paymentReference->getUser();
        $success = $user->wallet >= $paymentReference->getAmount();

        return $success
          ? successRes('', ['amount' => $paymentReference->getAmount()])
          : failRes('Insufficient wallet balance');
    }
}
