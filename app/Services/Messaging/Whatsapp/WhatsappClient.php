<?php

namespace App\Services\Messaging\Whatsapp;

use App\Support\Res;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappClient
{
  public function __construct()
  {
  }

  /**
   * @param array{
   *   messaging_product: string,
   *   to: string,
   *   type: string,
   *   ...mixed
   * }[] $multiplePayload
   */
  function send(array $multiplePayload)
  {
    $token = config('services.facebook.whatsapp-access-token');
    $phoneNumberId = config('services.facebook.whatsapp-phone-number-id');
    $apiVersion = config('services.facebook.whatsapp-api-version');
    if (!$token || !$phoneNumberId) {
      Log::warning('WhatsApp Cloud API credentials are not configured.');
      return;
    }
    foreach ($multiplePayload as $key => $payload) {
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
    $phoneNumberId = config('services.facebook.whatsapp-phone-number-id');
    $apiVersion = config('services.facebook.whatsapp-api-version');
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

  function sendHelloMessage(string $receiverPhoneNumber): Res
  {
    $token = config('services.facebook.whatsapp-access-token');
    $phoneNumberId = config('services.facebook.whatsapp-phone-number-id');
    $apiVersion = config('services.facebook.whatsapp-api-version');
    $payload = [
      'messaging_product' => 'whatsapp',
      'to' => (new PhoneNumberNormalizer())->normalize($receiverPhoneNumber),
      'type' => 'template',
      'template' => [
        'name' => 'hello_world', // your template name
        'language' => ['code' => 'en_US'],
        'components' => []
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
