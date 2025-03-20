<?php

namespace App\Enums;

enum EventType: string
{
  case StudentTest = 'student-test';
  case AdmissionExam = 'admission-exam';
  case RecruitmentTest = 'recruitment-test';
}
