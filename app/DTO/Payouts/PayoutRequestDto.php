<?php

namespace App\DTO\Payouts;

use App\Enums\PayoutPurposeType;
use Illuminate\Database\Eloquent\Model;

class PayoutRequestDto
{
  public function __construct(
    public Model $payoutable,
    public PayoutPurposeType $purpose,
    public float $amount,
    public PayoutBeneficiaryDto $beneficiary,
    public string $narration,
    public string $reference,
    public string $currency = 'NGN'
  ) {
  }
}
