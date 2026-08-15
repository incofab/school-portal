<?php

namespace App\Enums;

enum PayoutPurposeType: string
{
  case Withdrawal = 'withdrawal';
  case Payroll = 'payroll';
}
