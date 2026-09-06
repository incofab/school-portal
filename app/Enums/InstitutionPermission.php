<?php

namespace App\Enums;

enum InstitutionPermission: string
{
  case ManageRoles = 'manage-roles';
  case ManageAdmissions = 'manage-admissions';
  case ManageRecruitment = 'manage-recruitment';
  case ManageLibrary = 'manage-library';
  case ManageAttendance = 'manage-attendance';
  case ManageFees = 'manage-fees';
  case ManageFinance = 'manage-finance';
  case ManagePayroll = 'manage-payroll';
  case ManageNotifications = 'manage-notifications';
  case ManageChat = 'manage-chat';

  public static function defaultPermissions(InstitutionUserType $role): array
  {
    return match ($role) {
      InstitutionUserType::Admin => ['*'],
      InstitutionUserType::Teacher => [
        self::ManageLibrary,
        self::ManageAttendance,
        self::ManageChat
      ],
      InstitutionUserType::Accountant => [
        self::ManageFees,
        self::ManageFinance,
        self::ManagePayroll,
        self::ManageNotifications,
        self::ManageChat
      ],
      InstitutionUserType::Student,
      InstitutionUserType::Alumni,
      InstitutionUserType::Guardian => [self::ManageChat]
    };
  }
}
