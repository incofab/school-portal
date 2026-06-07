<?php

namespace App\Enums\Audit;

use App\Traits\EnumToArray;

enum ActivityLogCategory: string
{
  use EnumToArray;

  case System = 'system';
  case Authentication = 'authentication';
  case Authorization = 'authorization';
  case Security = 'security';
  case Impersonation = 'impersonation';
  case Institution = 'institution';
  case InstitutionGroup = 'institution_group';
  case Manager = 'manager';
  case User = 'user';
  case Staff = 'staff';
  case Student = 'student';
  case Guardian = 'guardian';
  case Classification = 'classification';
  case Course = 'course';
  case Curriculum = 'curriculum';
  case Assignment = 'assignment';
  case Attendance = 'attendance';
  case Assessment = 'assessment';
  case Result = 'result';
  case Exam = 'exam';
  case Admission = 'admission';
  case Fee = 'fee';
  case Payment = 'payment';
  case Wallet = 'wallet';
  case Payroll = 'payroll';
  case Expense = 'expense';
  case Messaging = 'messaging';
  case Notification = 'notification';
  case Timetable = 'timetable';
  case LiveClass = 'live_class';
  case Association = 'association';
  case Integration = 'integration';
  case ImportExport = 'import_export';
  case Media = 'media';
  case Settings = 'settings';
  case Report = 'report';
}
