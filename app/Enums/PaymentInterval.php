<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum PaymentInterval: string
{
  use EnumToArray;

  case Termly = 'termly';
  case Yearly = 'yearly';
  // case Monthly = 'monthly';
}
