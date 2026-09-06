<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum RoleGuard: string
{
  use EnumToArray;

  case Web = 'web';
  case Api = 'api';
}
