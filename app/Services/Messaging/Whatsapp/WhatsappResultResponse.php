<?php

namespace App\Services\Messaging\Whatsapp;

class WhatsappResultResponse
{
  public function __construct(
    public readonly string $message,
    public readonly bool $hasResultLink = false
  ) {
  }
}
