<?php

namespace App\Enums\PriceLists;

enum PaymentStructure: string
{
  case PerUnit = 'per-unit';
  case PerTerm = 'per-term';
  case PerSession = 'per-session';
  case PerStudentPerTerm = 'per-student-per-term';
  case PerStudentPerSession = 'per-student-per-session';
}
