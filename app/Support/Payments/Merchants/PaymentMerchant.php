<?php

namespace App\Support\Payments\Merchants;

use App\Contracts\Payments\PaymentRecord;
use App\DTO\PaymentReferenceDto;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentStatus;
use App\Models\PaymentReference;
use App\Models\User;
use App\Support\Res;
use Exception;

abstract class PaymentMerchant
{
  protected string $merchant;

  protected function __construct(string $merchant)
  {
    $this->merchant = $merchant;
  }

  function isManualPayment(): bool
  {
    return $this->merchant === PaymentMerchantType::Manual->value;
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
   *
   * @return array{0: \App\Support\Res, 1: \App\Models\PaymentReference} that is [$ret, $paymentReference]
   * */
  abstract public function init(
    PaymentReferenceDto $paymentReferenceDto,
    bool $generateReferenceOnly = false
  );

  public function completePayment(
    PaymentRecord $paymentReference,
    ?User $user = null
  ) {
    $paymentReference->confirmPayment($user);
  }

  abstract public function verify(PaymentRecord $paymentReference): Res;

  /**
   * @return static
   * */
  public static function make(?string $merchant = null)
  {
    $merchant = $merchant ?? PaymentMerchantType::Monnify->value;
    switch ($merchant) {
      case PaymentMerchantType::Paystack->value:
        return new PaymentPaystack($merchant);
      case PaymentMerchantType::Rave->value:
        return new PaymentRave($merchant);
      case PaymentMerchantType::UserWallet->value:
        return new PaymentWallet($merchant);
      case PaymentMerchantType::Manual->value:
        return new PaymentManual($merchant);
      case PaymentMerchantType::Monnify->value:
        return new PaymentMonnify($merchant);
      default:
        throw new Exception("Invalid merchant ($merchant)");
        break;
    }
  }
}
