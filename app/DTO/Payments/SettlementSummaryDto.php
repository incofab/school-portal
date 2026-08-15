<?php

namespace App\DTO\Payments;

use JsonSerializable;

class SettlementSummaryDto implements JsonSerializable
{
  /**
   * @param SettlementFailureDto[] $failures
   */
  public function __construct(
    public int $institutionsChecked = 0,
    public int $settled = 0,
    public int $skipped = 0,
    public int $failed = 0,
    public float $totalAmount = 0.0,
    public array $failures = []
  ) {
  }

  public function recordCheckedInstitution(): void
  {
    $this->institutionsChecked++;
  }

  public function recordFailure(SettlementFailureDto $failure): void
  {
    $this->failed++;
    $this->failures[] = $failure;
  }

  public function recordResult(SettlementInstitutionResultDto $result): void
  {
    if ($result->settled) {
      $this->settled++;
      $this->totalAmount += $result->amount;

      return;
    }

    $this->skipped++;
  }

  public function toArray(): array
  {
    return [
      'institutions_checked' => $this->institutionsChecked,
      'settled' => $this->settled,
      'skipped' => $this->skipped,
      'failed' => $this->failed,
      'total_amount' => $this->totalAmount,
      'failures' => array_map(
        fn(SettlementFailureDto $failure) => $failure->toArray(),
        $this->failures
      )
    ];
  }

  public function jsonSerialize(): mixed
  {
    return $this->toArray();
  }
}
