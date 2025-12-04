<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Services\Messaging\Whatsapp\WhatsappClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappMessage implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /**
   * @param array{
   *   messaging_product: string,
   *   to: string,
   *   type: string,
   *   ...mixed
   * }[] $multiplePayload
   */
  public function __construct(
    private array $multiplePayload,
    private ?Message $messageModel = null
  ) {
  }

  public function handle(): void
  {
    (new WhatsappClient($this->multiplePayload))->send();

    $this->markSent();
  }

  private function markSent(): void
  {
    if (!$this->messageModel) {
      return;
    }

    $this->messageModel
      ->fill([
        'status' => MessageStatus::Sent->value,
        'sent_at' => now()
      ])
      ->save();
  }
}
