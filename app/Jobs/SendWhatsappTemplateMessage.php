<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Services\Messaging\Whatsapp\Templates\WhatsappTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsappTemplateMessage implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct(
    private WhatsappTemplate $whatsappTemplate,
    private ?Message $messageModel = null
  ) {
  }

  public function handle(): void
  {
    $response = $this->whatsappTemplate->send();

    if ($response->isSuccessful()) {
      $this->markSent();
    }
  }

  private function markSent(): void
  {
    if (!$this->messageModel || $this->messageModel->isSent()) {
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
