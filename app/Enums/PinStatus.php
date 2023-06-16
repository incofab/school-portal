<?php

namespace App\Enums;

enum PinStatus: string
{
  const Active = 'active';
  const Printed = 'printed';
  const Used = 'used';
}
