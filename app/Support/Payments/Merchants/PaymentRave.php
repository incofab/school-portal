<?php

namespace App\Support\Payments\Merchants;

// use App\Core\RaveHelper;
use App\Contracts\Payments\PaymentRecord;
use App\DTO\PaymentReferenceDto;
use App\Support\Res;

class PaymentRave extends PaymentMerchant
{
    public function init(
        PaymentReferenceDto $paymentReferenceDto,
        bool $generateReferenceOnly = false
    ) {
        $paymentReference = $this->createPaymentReference($paymentReferenceDto);
        $ret = successRes('', [
            'reference' => $paymentReference->reference,
        ]);

        if (! $generateReferenceOnly) {
            // $ret = RaveHelper::make()->initialize(
            //   $paymentReference->user,
            //   $paymentReference->amount,
            //   route('rave-callback'),
            //   $paymentReference->reference
            // );
        }

        $ret['amount'] = $paymentReferenceDto->amount;

        return [$ret, $paymentReference];
    }

    public function verify(PaymentRecord $paymentReference): Res
    {
        return failRes('');
        // return RaveHelper::make()->verifyReference($paymentReference->reference);
    }
}
