<?php

namespace App\Enums;

use App\Traits\EnumToArray;

enum InstitutionSettingType: string
{
  use EnumToArray;

  case Result = 'result';
  case CurrentTerm = 'current-term';
  case CurrentAcademicSession = 'current-academic-session';
  /** There's actually no need for this, What it does is already handled by  */
  case CurrentlyOnMidTerm = 'currently-on-mid-term';
  case UsesMidTermResult = 'uses-mid-term-result';
  case Stamp = 'stamp';
  case PaymentKeys = 'payment-keys';
  case ResultActivationRequired = 'result-activation-required';
}
