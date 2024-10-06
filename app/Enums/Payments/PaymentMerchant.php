<?php
namespace App\Enums\Payments;

enum PaymentMerchant: string
{
  case Paystack = 'paystack';
  case Rave = 'rave';
  case Monnify = 'monnify';
}
