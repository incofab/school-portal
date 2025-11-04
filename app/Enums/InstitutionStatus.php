<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum InstitutionStatus: string
{
  use EnumToArray;

  case Active = 'active';
  case Suspended = 'suspended';
}
