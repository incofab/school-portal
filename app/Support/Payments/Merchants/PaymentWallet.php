<?php
namespace App\Support\Payments\Merchants;

use App\DTO\PaymentReferenceDto;
use App\Enums\TransactionType;
use App\Models\PaymentReference;
use App\Support\Res;
use App\Support\UserTransactionHandler;

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

    UserTransactionHandler::recordTransaction(
      amount: $paymentReference->amount,
      entity: $paymentReference->user,
      transactionType: TransactionType::Debit,
      transactionable: $paymentReference,
      reference: $paymentReference->reference
    );
  }

  function verify(PaymentReference $paymentReference): Res
  {
    $user = $paymentReference->user;
    $success = $user->wallet >= $paymentReference->amount;
    return $success ? successRes('') : failRes('Insufficient wallet balance');
  }
}
