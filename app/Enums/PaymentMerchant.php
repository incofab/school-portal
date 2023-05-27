<?php
namespace App\Enums;

enum PaymentMerchant: string
{
  case Paystack = 'paystack';
  case Rave = 'rave';
  case Monnify = 'monnify';
}
