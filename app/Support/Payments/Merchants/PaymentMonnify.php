<?php
namespace App\Support\Payments\Merchants;

use App\Core\MonnifyHelper;
use App\DTO\PaymentReferenceDto;
use App\Models\PaymentReference;
use App\Support\Res;

class PaymentMonnify extends PaymentMerchant
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

  function verify(PaymentReference $paymentReference): Res
  {
    return MonnifyHelper::make()->getTransactionStatus(
      $paymentReference->reference
    );
  }
}
