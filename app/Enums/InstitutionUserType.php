<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum InstitutionUserType: string
{
  use EnumToArray;

  case Admin = 'admin';
  case Student = 'student';
  case Teacher = 'teacher';
  case Accountant = 'accountant';
  case Alumni = 'alumni';
  case Guardian = 'guardian';
  case Others = 'others';

  public static function nonStaffRoles(): array
  {
    return [self::Student->value, self::Alumni->value, self::Guardian->value];
  }

  public static function studentRoles(): array
  {
    return [self::Student->value, self::Alumni->value];
  }

  /** @return array<self> */
  public static function staffTypes(): array
  {
    return [self::Admin, self::Teacher, self::Accountant, self::Others];
  }

  public function description(): string
  {
    return match ($this) {
      self::Admin => 'Full access to manage the school and its users.',
      self::Teacher => 'Teaching and academic operations for school staff.',
      self::Accountant
        => 'Finance, payroll, payment, and attendance operations.',
      self::Student => 'Access for currently enrolled students.',
      self::Alumni => 'Access for former students and alumni.',
      self::Guardian
        => 'Access for parents and guardians to follow dependants.',
      self::Others => 'Access for other staff and non-academic personnel.'
    };
  }
}
