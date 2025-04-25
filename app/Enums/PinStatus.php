<?php

namespace App\Enums;

enum PinStatus: string
{
  case Active = 'active';
  case Printed = 'printed';
  case Used = 'used';
}
