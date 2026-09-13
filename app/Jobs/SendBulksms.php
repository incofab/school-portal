<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\Message;
use App\Traits\ChargesInstitutionMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SendBulksms implements ShouldQueue
{
  use ChargesInstitutionMessage;
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  private int $recipientCount;

  private string $chargeReference;

  public function __construct(
    private string $message,
    private string $to,
    private ?Message $messageModel = null,
    private ?Institution $institution = null,
    ?int $recipientCount = null,
    ?string $chargeReference = null
  ) {
    $this->afterCommit();

    $this->recipientCount =
      $recipientCount ??
      collect(explode(',', $to))
        ->filter()
        ->count();
    $this->chargeReference = $chargeReference ?? Str::orderedUuid()->toString();
  }

  public function getTo()
  {
    return $this->to;
  }

  /**
   * Execute the job.
   */
  public function handle(): void
  {
    if (
      $this->institution &&
      !$this->chargeInstitutionMessage(
        $this->institution,
        NotificationChannelsType::Sms,
        $this->recipientCount,
        $this->messageModel,
        $this->chargeReference
      )
    ) {
      return;
    }

    $data = [
      'body' => $this->message,
      'from' => $this->institution?->name ?? config('app.name'),
      'to' => $this->to,
      'api_token' => config('services.bulksms_nigeria.api-token'),
      'gateway' => 'direct-refund'
    ];

    $res = Http::withToken(config('services.bulksms_nigeria.api-token'))
      ->withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
      ])
      ->post('https://www.bulksmsnigeria.com/api/v2/sms', $data);

    if ($res->failed()) {
      // Log::warning('BulkSMS delivery failed.', [
      //   'to' => $this->to,
      //   'status' => $res->status(),
      //   'body' => $res->body()
      // ]);

      $this->messageModel
        ?->fill([
          'status' => MessageStatus::Failed->value
        ])
        ->save();

      return;
    }

    $this->messageModel
      ?->fill([
        'status' => MessageStatus::Sent->value,
        'body' => $this->message,
        'sent_at' => now()
      ])
      ->save();
  }
}
