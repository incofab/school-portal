<?php
namespace App\Support\Payments\Processors;

use App\Support\Fundings\FundingHandler;
use App\Support\Res;
use DB;

class WalletFundingProcessor extends PaymentProcessor
{
  function processPayment(): Res
  {
    $res = $this->verify();

    if ($res->isNotSuccessful()) {
      return $res;
    }

    DB::beginTransaction();

    $this->paymentMerchant->completePayment($this->paymentReference);

    $res = FundingHandler::makeFromPaymentRef(
      $this->paymentReference,
      'Wallet funding'
    )->processWalletPayment($this->paymentReference);

    DB::commit();

    return successRes('Payment processed successfully');
  }
}
