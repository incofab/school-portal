<?php

namespace App\Enums;

enum EmailStatus: string
{
  case Sent = 'sent';
  case Pending = 'pending';
  case Failed = 'failed';
}
