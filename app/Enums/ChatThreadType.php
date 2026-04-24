<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum ChatThreadType: string
{
  use EnumToArray;

  case DirectUser = 'direct-user';
  case Institution = 'institution';
  case Role = 'role';
}
