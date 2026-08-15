<?php

namespace App\DTO\Payouts;

class PayoutBatchRequestDto
{
  /**
   * @param PayoutRequestDto[] $items
   */
  public function __construct(
    public string $batchReference,
    public string $title,
    public string $narration,
    public array $items
  ) {
  }
}
