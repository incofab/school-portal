<?php

namespace App\Enums\Payments;

enum PaymentMerchantType: string
{
  case Monnify = 'monnify';
  case PaymentPoint = 'payment-point';
  case Paystack = 'paystack';
  case Rave = 'rave';
  case UserWallet = 'user-wallet';
  case Manual = 'manual';

  static function settleable(): array
  {
    return [
      self::Monnify,
      self::PaymentPoint,
      self::Paystack,
      self::Rave,
      self::UserWallet
    ];
  }

  static function walletCreditable(): array
  {
    return [
      self::Monnify,
      self::PaymentPoint,
      self::Paystack,
      self::Rave,
      self::UserWallet
    ];
  }

  static function payouts(): array
  {
    return [self::Monnify, self::PaymentPoint, self::Paystack, self::Rave];
  }

  static function getDefault(): string
  {
    return self::Paystack->value;
  }
}
