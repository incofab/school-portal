<?php
namespace App\Enums\Payments;

enum PaymentMerchantType: string
{
  case Paystack = 'paystack';
  case Rave = 'rave';
  case Monnify = 'monnify';
  case UserWallet = 'user-wallet';
}
