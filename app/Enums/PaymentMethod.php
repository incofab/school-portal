<?php
namespace App\Enums;

enum PaymentMethod: string
{
  case Wallet = 'wallet';
  case Card = 'card';
  case Bank = 'bank';
}
