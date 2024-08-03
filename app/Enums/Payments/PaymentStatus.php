<?php

namespace App\Enums\Payments;

enum PaymentStatus: string
{
  case Pending = 'pending';
  case Confirmed = 'confirmed';
  case Processing = 'processing';
}
