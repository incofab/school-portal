<?php

namespace App\DTO\Payouts;

class PayoutBatchResultDto
{
  /**
   * @param PayoutItemResultDto[] $items
   */
  public function __construct(
    public string $batchReference,
    public string $merchantStatus,
    public string $message,
    public array $items = [],
    public array $providerResponse = []
  ) {
  }
}
