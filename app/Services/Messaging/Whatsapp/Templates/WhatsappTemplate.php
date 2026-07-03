<?php
namespace App\Services\Messaging\Whatsapp\Templates;

use App\Support\Res;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class WhatsappTemplate
{
  protected string $token;

  protected string $phoneNumberId;

  protected string $apiVersion;

  function __construct(
    private string $templateName,
    private string $receiverPhoneNumber
  ) {
    $this->token = config('services.facebook.whatsapp-access-token');
    $this->phoneNumberId = config('services.facebook.whatsapp-phone-number-id');
    $this->apiVersion = config('services.facebook.whatsapp-api-version');

    if (!$this->token || !$this->phoneNumberId) {
      Log::warning('WhatsApp Cloud API credentials are not configured.');
    }
  }

  abstract function payload(): array;

  function getTemplateName(): string
  {
    return $this->templateName;
  }

  function getReceiverPhoneNumber(): string
  {
    return $this->receiverPhoneNumber;
  }

  function send(): Res
  {
    $response = Http::withToken($this->token)
      ->withHeaders(['Content-Type' => 'application/json'])
      ->post(
        "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages",
        $this->payload()
      );

    if ($response->successful()) {
      return successRes('Message sent successfully', $response->json());
    }
    return failRes('Failed to send message', $response->json());
  }
}
