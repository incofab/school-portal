<?php

namespace App\Enums;

enum SettlementStatus: string
{
  case Completed = 'completed';
  case Failed = 'failed';
}
