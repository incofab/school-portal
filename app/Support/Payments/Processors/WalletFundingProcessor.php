<?php

namespace App\Support\Payments\Processors;

use App\Enums\Payments\PaymentMerchantType;
use App\Models\PaymentReference;
use App\Support\Fundings\FundingHandler;
use App\Support\Res;

class WalletFundingProcessor extends PaymentProcessor
{
  public function processPayment(): Res
  {
    abort_if(
      $this->paymentMerchant->isManualPayment(),
      402,
      'Manual payments cannot be used for wallet funding'
    );
    $res = $this->verify();

    if ($res->isNotSuccessful()) {
      return $res;
    }

    $this->paymentMerchant->completePayment(
      $this->paymentReference,
      $this->confirmingUser
    );

    if (!($this->paymentReference instanceof PaymentReference)) {
      return failRes('Manual wallet funding is not supported');
    }

    $res = FundingHandler::makeFromPaymentRef(
      $this->paymentReference,
      'Wallet funding'
    )->processWalletPayment($this->paymentReference);

    return successRes('Payment processed successfully');
  }
}
