<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum InstitutionUserType: string
{
  use EnumToArray;

  case Admin = 'admin';
  case Student = 'student';
  case Teacher = 'teacher';
  case Alumni = 'alumni';
  case Guardian = 'guardian';
}
