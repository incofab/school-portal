<?php

namespace App\Actions\Payments;

use App\Core\PaystackHelper;
use App\Enums\Payments\PaymentStatus;
use App\Models\PaymentReference;
use App\Support\Fundings\FundingHandler;
use DB;

/** @deprecated Moved to \App\Support\FundingProcessor */
class ConfirmWalletFunding
{
  public function __construct(private PaymentReference $paymentReference)
  {
  }

  function run()
  {
    $res = PaystackHelper::make()->verifyReference(
      $this->paymentReference->reference,
      $this->paymentReference->purpose->value
    );

    if ($res->isNotSuccessful()) {
      if ($res->is_failed) {
        $this->paymentReference
          ->fill(['status' => PaymentStatus::Cancelled])
          ->save();
      }
      return $res;
    }

    DB::beginTransaction();
    $this->paymentReference->confirmPayment();
    $res = FundingHandler::makeFromPaymentRef(
      $this->paymentReference
    )->processWalletPayment($this->paymentReference);
    DB::commit();

    return successRes('Payment recorded');
  }
}
