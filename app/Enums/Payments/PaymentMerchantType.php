<?php
namespace App\Enums\Payments;

enum PaymentMerchantType: string
{
  case Monnify = 'monnify';
  case PaymentPoint = 'payment-point';
  case Paystack = 'paystack';
  case Rave = 'rave';
  case UserWallet = 'user-wallet';
}
