<?php
namespace App\Support\Payments\Processors;

use App\Actions\Payments\FeePaymentHandler;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentStatus;
use App\Models\Fee;
use App\Support\Res;
use App\Support\TransactionHandler;
use DB;

class FeePaymentProcessor extends PaymentProcessor
{
  function processPayment(): Res
  {
    if ($this->paymentReference->status != PaymentStatus::Pending) {
      return failRes('Payment already resolved');
    }

    $res = $this->verify();

    if ($res->isNotSuccessful()) {
      return $res;
    }

    $fee = $this->paymentReference->paymentable;
    if (!($fee instanceof Fee)) {
      return failRes('Fee record not found');
    }

    DB::beginTransaction();
    $this->paymentMerchant->completePayment($this->paymentReference);
    $user = $this->paymentReference->payable;

    FeePaymentHandler::make($this->paymentReference->institution)->create(
      [
        'reference' => $this->paymentReference->reference,
        'user_id' => $user->id ?? $this->paymentReference->user_id,
        'amount' => $this->paymentReference->amount,
        'method' => PaymentMethod::Card->value
      ],
      $fee,
      $this->paymentReference->payable,
      allowOverPayment: true
    );

    TransactionHandler::makeFromPaymentReference(
      $this->paymentReference
    )->topupCreditWallet(
      $this->paymentReference->amount,
      $this->paymentReference,
      'Fee payment for: ' . $fee->title
    );

    DB::commit();

    return successRes('Fee Payment processed successfully');
  }
}
