<?php

namespace App\Services\Messaging\Whatsapp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappCloudApiService
{
  public function sendTextMessage(string $to, string $message): bool
  {
    $token = config('services.facebook.whatsapp-access-token');
    $phoneNumberId = config(
      'services.facebook.whatsapp-phone-number-id',
      '819996257873340'
    );
    $apiVersion = config('services.facebook.whatsapp-api-version', 'v22.0');

    if (!$token || !$phoneNumberId) {
      Log::warning('WhatsApp Cloud API credentials are not configured.');
      return false;
    }

    $response = Http::withToken($token)
      ->acceptJson()
      ->post(
        "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages",
        [
          'messaging_product' => 'whatsapp',
          'to' => app(PhoneNumberNormalizer::class)->normalize($to),
          'type' => 'text',
          'text' => [
            'preview_url' => true,
            'body' => $message
          ]
        ]
      );

    if ($response->failed()) {
      Log::warning('WhatsApp Cloud API message failed.', [
        'to' => $to,
        'status' => $response->status(),
        'body' => $response->body()
      ]);
      return false;
    }

    return true;
  }
}
