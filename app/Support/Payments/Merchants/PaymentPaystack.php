<?php
namespace App\Support\Payments\Merchants;

use App\Core\PaystackHelper;
use App\DTO\PaymentReferenceDto;
use App\Models\PaymentReference;
use App\Support\Res;
use Str;

class PaymentPaystack extends PaymentMerchant
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
      $email =
        $paymentReferenceDto->getPaymentable()?->email ??
        ($paymentReference->user?->email ?? $this->generateEmail());

      $ret = PaystackHelper::make()->initialize(
        $paymentReference->amount,
        $email,
        route('paystack.callback'),
        $paymentReference->reference
      );
    }

    $ret['amount'] = $paymentReferenceDto->amount;
    return [$ret, $paymentReference];
  }

  private function generateEmail($title = '')
  {
    $name = empty($title)
      ? Str::uuid()
      : str_replace(['+', ' ', '-', '_', '@'], '', $title);
    return "$name@edumanager.ng";
  }

  function verify(PaymentReference $paymentReference): Res
  {
    return PaystackHelper::make()->verifyReference(
      $paymentReference->reference
    );
  }
}
