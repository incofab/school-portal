<?php

namespace App\Support\Payments\Merchants;

use App\Contracts\Payments\PaymentRecord;
use App\Core\MonnifyHelper;
use App\DTO\PaymentReferenceDto;
use App\Support\Res;

class PaymentMonnify extends PaymentMerchant
{
    public function init(
        PaymentReferenceDto $paymentReferenceDto,
        bool $generateReferenceOnly = false
    ) {
        $paymentReference = $this->createPaymentReference($paymentReferenceDto);
        $ret = successRes('', [
            'reference' => $paymentReference->reference,
            'amount' => $paymentReferenceDto->amount,
            'authorization_url' => route('monnify.checkout', [
                'reference' => $paymentReference->reference,
            ]),
        ]);

        return [$ret, $paymentReference];
    }

    public function verify(PaymentRecord $paymentReference): Res
    {
        return MonnifyHelper::make()->getTransactionStatus(
            $paymentReference->getReference()
        );
    }
}
