<?php

namespace App\Support\Payments\Processors;

use App\Enums\Payments\PaymentMerchantType;
use App\Enums\TransactionType;
use App\Support\Res;
use App\Support\UserTransactionHandler;
use DB;

/** @deprecated Not in use at the moment */
class UserWalletFundingProcessor extends PaymentProcessor
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

    DB::beginTransaction();

    $this->paymentMerchant->completePayment(
      $this->paymentReference,
      $this->confirmingUser
    );

    UserTransactionHandler::recordTransaction(
      amount: $this->paymentReference->getAmount(),
      entity: $this->paymentReference->getPaymentable(),
      transactionType: TransactionType::Credit,
      transactionable: $this->paymentReference->getModel(),
      reference: $this->paymentReference->getReference(),
      remark: 'Wallet funding'
    );

    DB::commit();

    return successRes('Payment processed successfully');
  }
}
