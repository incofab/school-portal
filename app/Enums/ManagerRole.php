<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ManagerRole: string
{
  use EnumToArray;

  case Admin = 'admin';
  case Partner = 'partner';
}
