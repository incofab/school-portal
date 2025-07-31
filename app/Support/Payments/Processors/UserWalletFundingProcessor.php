<?php
namespace App\Support\Payments\Processors;

use App\Enums\TransactionType;
use App\Models\UserTransaction;
use App\Support\Res;
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

    $user = $this->paymentReference->paymentable;
    $amount = $this->paymentReference->amount;

    UserTransaction::Create([
      'type' => TransactionType::Credit,
      'amount' => $amount,
      'bbt' => $user->wallet,
      'bat' => $user->wallet + $amount,
      'entity_type' => $user->getMorphClass(),
      'entity_id' => $user->id,
      'transactionable_type' => $this->paymentReference->getMorphClass(),
      'transactionable_id' => $this->paymentReference->id,
      'reference' => $this->paymentReference->reference,
      'remark' => 'Wallet funding'
    ]);

    $user->fill(['wallet' => $user->wallet + $amount])->save();
    DB::commit();

    return successRes('Payment processed successfully');
  }
}
