<?php

namespace App\DTO\Payments;

use App\Models\Payout;
use Illuminate\Database\Eloquent\Model;

class PayoutPreparationDto
{
  public function __construct(
    public ?Model $payoutable,
    public ?Payout $payout,
    public bool $hadExistingAttempt = false,
    public bool $alreadyProcessing = false
  ) {
  }
}
