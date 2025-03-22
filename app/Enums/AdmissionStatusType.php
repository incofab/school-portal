<?php

namespace App\Enums;

enum AdmissionStatusType: string
{
  case Pending = 'pending';
  case Admitted = 'admitted';
  case Declined = 'declined';
}
