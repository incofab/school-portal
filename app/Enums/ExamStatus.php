<?php

namespace App\Enums;

enum ExamStatus: string
{
  case Active = 'active';
  case Ended = 'ended';
  case Pending = 'pending';
  case Paused = 'paused';
}
