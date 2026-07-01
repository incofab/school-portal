<?php

namespace App\Services\Messaging\Whatsapp;

use App\Support\Res;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    $token = config('services.facebook.whatsapp-access-token');
    $phoneNumberId = config(
      'services.facebook.whatsapp-phone-number-id',
      '819996257873340'
    );
    $apiVersion = config('services.facebook.whatsapp-api-version', 'v22.0');
    if (!$token || !$phoneNumberId) {
      Log::warning('WhatsApp Cloud API credentials are not configured.');
      return;
    }
    foreach ($this->multiplePayload as $key => $payload) {
      $response = Http::withToken($token)
        ->withHeaders(['Content-Type' => 'application/json'])
        ->post(
          "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages",
          $payload
        );
      info($response->body());
    }
  }

  function sendUtilityMessage(
    string $receiverPhoneNumber,
    string $message,
    string $schoolName,
    string|null $receiverName = ''
  ): Res {
    $token = config('services.facebook.whatsapp-access-token');
    $phoneNumberId = config(
      'services.facebook.whatsapp-phone-number-id',
      '819996257873340'
    );
    $apiVersion = config('services.facebook.whatsapp-api-version', 'v22.0');

    $payload = [
      'messaging_product' => 'whatsapp',
      'to' => (new PhoneNumberNormalizer())->normalize($receiverPhoneNumber), //'2347036098561', // recipient phone number in international format
      'type' => 'template',
      'template' => [
        'name' => 'utility_message', // your template name
        'language' => [
          'code' => 'en_US'
        ],
        'components' => [
          [
            'type' => 'body',
            'parameters' => [
              [
                'type' => 'text',
                'text' => $schoolName
              ],
              [
                'type' => 'text',
                'text' => $receiverName
              ],
              [
                'type' => 'text',
                'text' => $message
              ]
            ]
          ]
        ]
      ]
    ];
    $response = Http::withToken($token)
      ->withHeaders(['Content-Type' => 'application/json'])
      ->post(
        "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages",
        $payload
      );

    if ($response->successful()) {
      return successRes('Message sent successfully', $response->json());
    }
    return failRes('Failed to send message', $response->json());
  }
}
