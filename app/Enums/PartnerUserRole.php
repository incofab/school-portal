<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum PartnerUserRole: string
{
  use EnumToArray;

  case Admin = 'admin';
  case Staff = 'staff';
}
