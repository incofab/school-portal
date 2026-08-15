<?php

namespace App\DTO\Payments;

use App\Models\Payout;
use JsonSerializable;

class PayoutProcessingResultDto implements JsonSerializable
{
  public function __construct(
    public string $group,
    public int $payoutableId,
    public string $payoutableType,
    public string $status,
    public string $note,
    public ?int $payoutId = null,
    public ?string $reference = null,
    public ?string $batchReference = null
  ) {
  }

  public static function fromPayout(
    Payout $payout,
    string $group,
    string $status,
    string $note
  ): self {
    return new self(
      group: $group,
      payoutableId: $payout->payoutable_id,
      payoutableType: $payout->payoutable_type,
      status: $status,
      note: $note,
      payoutId: $payout->id,
      reference: $payout->reference,
      batchReference: $payout->batch_reference
    );
  }

  public function toArray(): array
  {
    return array_filter(
      [
        'group' => $this->group,
        'payoutable_id' => $this->payoutableId,
        'payoutable_type' => $this->payoutableType,
        'payout_id' => $this->payoutId,
        'reference' => $this->reference,
        'batch_reference' => $this->batchReference,
        'status' => $this->status,
        'note' => $this->note
      ],
      fn($value) => $value !== null
    );
  }

  public function jsonSerialize(): mixed
  {
    return $this->toArray();
  }
}
