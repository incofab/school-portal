<?php
namespace App\Support\Payments\Processors;

use App\Support\Fundings\FundingHandler;
use App\Support\Res;
use DB;

class WalletFundingProcessor extends PaymentProcessor
{
  function handleCallback(): Res
  {
    $res = $this->verify();

    if ($res->isNotSuccessful()) {
      return $res;
    }

    DB::beginTransaction();

    $this->paymentReference->confirmPayment();

    $res = FundingHandler::makeFromPaymentRef(
      $this->paymentReference
    )->processWalletPayment($this->paymentReference);

    DB::commit();

    return successRes('Payment processed successfully');
  }
}
