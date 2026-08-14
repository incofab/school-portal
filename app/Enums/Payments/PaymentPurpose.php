<?php

namespace App\Enums\Payments;

enum PaymentPurpose: string
{
  case Fee = 'fee';
  case WalletFunding = 'wallet-funding';
  case AdmissionFormPurchase = 'admission-form-purchase';

  static function settleable(): array
  {
    return [self::Fee, self::AdmissionFormPurchase];
  }
}
