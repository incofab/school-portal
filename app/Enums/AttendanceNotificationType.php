<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum AttendanceNotificationType: string
{
  use EnumToArray;

  case None = 'none';
  case CheckIn = 'check-in';
  case CheckInAndOut = 'check-in-and-out';
  case CheckOut = 'check-out';
}
