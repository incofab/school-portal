<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ReceiptStatus: string
{
  use EnumToArray;

  case Paid = 'paid';
  case Partial = 'partial';
}
