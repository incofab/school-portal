<?php

namespace App\Enums;

enum EmailRecipientType: string
{
  case AllStudents = 'all-students';
  case AllTeachers = 'all-teachers';
  case Classification = 'classification';
  case ClassificationGroup = 'classification-group';
  case Institution = 'institution';
  case Single = 'single';
  case Multiple = 'multiple';
}
