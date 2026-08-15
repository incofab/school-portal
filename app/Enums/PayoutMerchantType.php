<?php

namespace App\Enums;

enum PayoutMerchantType: string
{
  case Monnify = 'monnify';
  case Paystack = 'paystack';

  public static function default(): self
  {
    return self::from(config('services.payout.merchant', self::Monnify->value));
  }
}
