<?php
namespace App\Support\Payments\Processors;

use App\Enums\TransactionType;
use App\Support\Res;
use App\Support\UserTransactionHandler;
use DB;

class UserWalletFundingProcessor extends PaymentProcessor
{
  function processPayment(): Res
  {
    $res = $this->verify();

    if ($res->isNotSuccessful()) {
      return $res;
    }

    DB::beginTransaction();

    $this->paymentMerchant->completePayment($this->paymentReference);

    UserTransactionHandler::recordTransaction(
      amount: $this->paymentReference->amount,
      entity: $this->paymentReference->paymentable,
      transactionType: TransactionType::Credit,
      transactionable: $this->paymentReference,
      reference: $this->paymentReference->reference,
      remark: 'Wallet funding'
    );

    DB::commit();

    return successRes('Payment processed successfully');
  }
}
