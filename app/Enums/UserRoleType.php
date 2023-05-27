<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum UserRoleType: string
{
  use EnumToArray;

  case Admin = 'admin';
  case Student = 'student';
  case Teacher = 'teacher';
  case Alumni = 'alumni';
}
