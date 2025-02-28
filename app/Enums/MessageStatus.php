<?php

namespace App\Enums;

enum MessageStatus: string
{
  case Sent = 'sent';
  case Pending = 'pending';
  case Failed = 'failed';
}
