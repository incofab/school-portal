<?php

namespace App\Support\Payments\Processors;

use App\Models\PaymentReference;
use App\Support\Fundings\FundingHandler;
use App\Support\Res;
use DB;

class WalletFundingProcessor extends PaymentProcessor
{
    public function processPayment(): Res
    {
        $res = $this->verify();

        if ($res->isNotSuccessful()) {
            return $res;
        }

        DB::beginTransaction();

        $this->paymentMerchant->completePayment(
            $this->paymentReference,
            $this->confirmingUser
        );

        if (! ($this->paymentReference instanceof PaymentReference)) {
            DB::rollBack();

            return failRes('Manual wallet funding is not supported');
        }

        $res = FundingHandler::makeFromPaymentRef(
            $this->paymentReference,
            'Wallet funding'
        )->processWalletPayment($this->paymentReference);

        DB::commit();

        return successRes('Payment processed successfully');
    }
}
