<?php

namespace App\Enums;

enum NotificationReceiversType: string
{
  case AllClasses = 'all-classes';
  case SpecificClass = 'specific-class';
}
