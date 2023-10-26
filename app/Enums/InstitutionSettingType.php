<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum InstitutionSettingType: string
{
  use EnumToArray;

  case ResultTemplate = 'result-template';
  case CurrentTerm = 'current-term';
  case CurrentAcademicSession = 'current-academic-session';
  case CurrentlyOnMidTerm = 'currently-on-mid-term';
  case UsesMidTermResult = 'uses-mid-term-result';
  case Stamp = 'stamp';
}
