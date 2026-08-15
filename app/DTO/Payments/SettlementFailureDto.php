<?php

namespace App\DTO\Payments;

use JsonSerializable;

class SettlementFailureDto implements JsonSerializable
{
  public function __construct(public int $institutionId, public string $message)
  {
  }

  public function toArray(): array
  {
    return [
      'institution_id' => $this->institutionId,
      'message' => $this->message
    ];
  }

  public function jsonSerialize(): mixed
  {
    return $this->toArray();
  }
}
