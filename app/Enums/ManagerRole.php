<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ManagerRole: string
{
  use EnumToArray;

  case ManagerAdmin = 'manager-admin';
  case Partner = 'partner';
}
