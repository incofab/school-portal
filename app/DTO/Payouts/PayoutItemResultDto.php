<?php

namespace App\DTO\Payouts;

use App\Enums\PayoutStatus;

class PayoutItemResultDto
{
  public function __construct(
    public string $reference,
    public PayoutStatus $status,
    public string $merchantStatus,
    public string $message,
    public ?string $providerReference = null,
    public array $providerResponse = []
  ) {
  }
}
