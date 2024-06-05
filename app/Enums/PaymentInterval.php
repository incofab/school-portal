<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum PaymentInterval: string
{
  use EnumToArray;

  case OneTime = 'one-time';
  case Termly = 'termly';
  case Sessional = 'sessional';
  // case Monthly = 'monthly';
}
