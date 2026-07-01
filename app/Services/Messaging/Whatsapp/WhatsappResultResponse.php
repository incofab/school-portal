<?php

namespace App\Services\Messaging\Whatsapp;

class WhatsappResultResponse
{
  public function __construct(
    public readonly string $message,
    public readonly bool $hasResultLink = false,
    public readonly ?string $state = null,
    public readonly array $stateData = []
  ) {
  }
}
