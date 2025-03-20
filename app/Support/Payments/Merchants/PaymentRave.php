<?php
namespace App\Support\Payments\Merchants;

use App\Core\RaveHelper;
use App\DTO\PaymentReferenceDto;
use App\Models\PaymentReference;
use App\Support\Res;

class PaymentRave extends PaymentMerchant
{
  function init(
    PaymentReferenceDto $paymentReferenceDto,
    bool $generateReferenceOnly = false
  ) {
    $paymentReference = self::createPaymentReference($paymentReferenceDto);
    $ret = successRes('', [
      'reference' => $paymentReference->reference
    ]);

    if (!$generateReferenceOnly) {
      // $ret = RaveHelper::make()->initialize(
      //   $paymentReference->user,
      //   $paymentReference->amount,
      //   route('rave-callback'),
      //   $paymentReference->reference
      // );
    }

    $ret['amount'] = $paymentReferenceDto->amount;
    return [$ret, $paymentReference];
  }

  function verify(PaymentReference $paymentReference): Res
  {
    // return RaveHelper::make()->verifyReference($paymentReference->reference);
  }
}
