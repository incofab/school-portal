<?php

namespace App\DTO\Payments;

use JsonSerializable;

class PayoutProcessingSummaryDto implements JsonSerializable
{
  /**
   * @param PayoutProcessingResultDto[] $items
   */
  public function __construct(
    public int $processed = 0,
    public int $successful = 0,
    public int $pending = 0,
    public int $failed = 0,
    public int $skipped = 0,
    public array $items = []
  ) {
  }

  public function record(PayoutProcessingResultDto $result): void
  {
    $this->processed++;

    if (property_exists($this, $result->group)) {
      $this->{$result->group}++;
    }

    $this->items[] = $result;
  }

  public function message(): string
  {
    return "{$this->processed} processed: {$this->successful} successful, {$this->pending} pending, {$this->failed} failed.";
  }

  public function toArray(): array
  {
    return [
      'processed' => $this->processed,
      'successful' => $this->successful,
      'pending' => $this->pending,
      'failed' => $this->failed,
      'skipped' => $this->skipped,
      'items' => array_map(
        fn(PayoutProcessingResultDto $item) => $item->toArray(),
        $this->items
      )
    ];
  }

  public function jsonSerialize(): mixed
  {
    return $this->toArray();
  }
}
