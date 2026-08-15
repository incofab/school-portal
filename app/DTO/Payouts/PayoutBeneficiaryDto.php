<?php

namespace App\DTO\Payouts;

class PayoutBeneficiaryDto
{
  public function __construct(
    public string $accountNumber,
    public string $bankCode,
    public string $accountName,
    public ?string $bankName = null,
    public ?string $email = null
  ) {
  }
}
