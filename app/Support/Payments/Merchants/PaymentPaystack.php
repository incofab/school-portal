<?php

namespace App\Support\Payments\Merchants;

use App\Contracts\Payments\PaymentRecord;
use App\Core\PaystackHelper;
use App\DTO\PaymentReferenceDto;
use App\Support\Res;
use Str;

class PaymentPaystack extends PaymentMerchant
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
            $email =
              $paymentReferenceDto->getPaymentable()?->email ??
              ($paymentReference->user?->email ?? $this->generateEmail());

            $ret = PaystackHelper::make()->initialize(
                $paymentReference->amount,
                $email,
                route('paystack.callback'),
                $paymentReference->reference
            );
        }

        $ret['amount'] = $paymentReferenceDto->amount;

        return [$ret, $paymentReference];
    }

    private function generateEmail($title = '')
    {
        $name = empty($title)
          ? Str::uuid()
          : str_replace(['+', ' ', '-', '_', '@'], '', $title);

        return "$name@edumanager.ng";
    }

    public function verify(PaymentRecord $paymentReference): Res
    {
        return PaystackHelper::make()->verifyReference(
            $paymentReference->getReference()
        );
    }
}
