<?php

namespace App\Services\Messaging\Whatsapp;

use Illuminate\Support\Facades\Http;

class WhatsappClient
{
  /**
   * @param array{
   *   messaging_product: string,
   *   to: string,
   *   type: string,
   *   ...mixed
   * }[] $multiplePayload
   */
  public function __construct(private array $multiplePayload)
  {
  }

  function send()
  {
    foreach ($this->multiplePayload as $key => $payload) {
      // info('Sending WhatsApp message... 1');
      $response = Http::withToken(
        config('services.facebook.whatsapp-access-token')
      )
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post(
          'https://graph.facebook.com/v22.0/819996257873340/messages',
          $payload
        );
      info($response->body());
    }
  }
}
