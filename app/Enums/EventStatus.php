<?php

namespace App\Enums;

enum EventStatus: string
{
  case Active = 'active';
  case Ended = 'ended';
  case Pending = 'pending';
  case Paused = 'paused';
}
