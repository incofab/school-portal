<?php

namespace App\Enums\Payments;

enum PaymentPurpose: string
{
  case Fee = 'fee';
  case WalletFunding = 'wallet-funding';
  case AdmissionFormPurchase = 'admission-form-purchase';
}
