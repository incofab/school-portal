<?php

namespace App\Enums;

enum PayoutStatus: string
{
  case Initiated = 'initiated';
  case Successful = 'successful';
  case Pending = 'pending';
  case Failed = 'failed';
  case Unknown = 'unknown';
}
