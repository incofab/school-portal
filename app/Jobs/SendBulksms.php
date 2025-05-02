<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\Institution;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Http;

class SendBulksms implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function __construct(
    private string $message,
    private string $to,
    private ?Message $messageModel = null,
    private ?Institution $institution = null
  ) {
  }

  function getTo()
  {
    return $this->to;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    $data = [
      'body' => $this->message,
      'from' => $this->institution?->name ?? config('app.name'),
      'to' => $this->to,
      'api_token' => config('services.bulksms_nigeria.api-token'),
      'gateway' => 'direct-refund'
    ];

    Http::post('https://www.bulksmsnigeria.com/api/v2/sms', [
      'form_params' => $data
    ]);

    $this->messageModel
      ?->fill([
        'status' => MessageStatus::Sent->value,
        'body' => $this->message,
        'sent_at' => now()
      ])
      ->save();
  }
}
