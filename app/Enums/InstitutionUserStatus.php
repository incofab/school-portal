<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum InstitutionUserStatus: string
{
  use EnumToArray;

  case Active = 'active';
  case Suspended = 'suspended';
}
