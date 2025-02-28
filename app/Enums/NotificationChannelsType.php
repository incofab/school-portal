<?php

namespace App\Enums;

enum NotificationChannelsType: string
{
  case Email = 'email';
  case Sms = 'sms';
}
