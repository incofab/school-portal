<?php
namespace App\Enums\Payments;

enum PaymentMethod: string
{
  case Wallet = 'wallet';
  case Card = 'card';
  case Bank = 'bank';
}
