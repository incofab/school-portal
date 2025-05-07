<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
  case Pending = 'pending';
  case Paid = 'paid';
  case Declined = 'declined';
}
