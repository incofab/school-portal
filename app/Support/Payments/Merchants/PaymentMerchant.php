<?php
namespace App\Support\Payments\Merchants;

use App\DTO\PaymentReferenceDto;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentStatus;
use App\Models\PaymentReference;
use App\Support\Res;
use Exception;

abstract class PaymentMerchant
{
  protected string $merchant;

  protected function __construct(string $merchant)
  {
    $this->merchant = $merchant;
  }

  protected function createPaymentReference(
    PaymentReferenceDto $paymentReferenceDto
  ): PaymentReference {
    $post = [
      ...$paymentReferenceDto->toArray(),
      'status' => PaymentStatus::Pending->value
    ];

    return PaymentReference::query()->firstOrCreate(
      ['reference' => $paymentReferenceDto->reference],
      $post
    );
  }

  /**
   * $data Should contain amount|userId|etc
   * @return array{0: \App\Support\Res, 1: \App\Models\PaymentReference} that is [$ret, $paymentReference]
   * */
  abstract function init(
    PaymentReferenceDto $paymentReferenceDto,
    bool $generateReferenceOnly = false
  );

  function completePayment(PaymentReference $paymentReference)
  {
    $paymentReference->confirmPayment();
  }

  abstract function verify(PaymentReference $paymentReference): Res;

  /**
   * @param string $merchant
   * @return static
   * */
  public static function make(string $merchant)
  {
    switch ($merchant) {
      case PaymentMerchantType::Paystack->value:
        return new PaymentPaystack($merchant);
      case PaymentMerchantType::Rave->value:
        return new PaymentRave($merchant);
      case PaymentMerchantType::UserWallet->value:
        return new PaymentWallet($merchant);
      default:
        throw new Exception("Invalid merchant ($merchant)");
        break;
    }
  }
}
