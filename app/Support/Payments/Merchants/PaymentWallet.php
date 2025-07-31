<?php
namespace App\Support\Payments\Merchants;

use App\DTO\PaymentReferenceDto;
use App\Enums\TransactionType;
use App\Models\PaymentReference;
use App\Models\UserTransaction;
use App\Support\Res;
use Exception;

class PaymentWallet extends PaymentMerchant
{
  function init(
    PaymentReferenceDto $paymentReferenceDto,
    bool $generateReferenceOnly = false
  ) {
    $paymentReference = $this->createPaymentReference($paymentReferenceDto);
    $ret = successRes('', [
      'reference' => $paymentReference->reference,
      'amount' => $paymentReferenceDto->amount
    ]);
    return [$ret, $paymentReference];
  }

  function completePayment(PaymentReference $paymentReference): void
  {
    parent::completePayment($paymentReference);

    $user = $paymentReference->user;
    $amount = $paymentReference->amount;
    $bbt = $user->wallet - $amount;
    if ($bbt < 0) {
      throw new Exception('User wallet cannot be zero or less');
    }

    UserTransaction::Create([
      'type' => TransactionType::Debit,
      'amount' => $amount,
      'bbt' => $user->wallet,
      'bat' => $bbt,
      'entity_type' => $user->getMorphClass(),
      'entity_id' => $user->id,
      'transactionable_type' => $paymentReference->getMorphClass(),
      'transactionable_id' => $paymentReference->id,
      'reference' => $paymentReference->reference
      // 'remark' => ''
    ]);

    $user->fill(['wallet' => $bbt])->save();
  }

  function verify(PaymentReference $paymentReference): Res
  {
    $user = $paymentReference->user;
    $success = $user->wallet >= $paymentReference->amount;
    return $success ? successRes('') : failRes('Insufficient wallet balance');
  }
}
