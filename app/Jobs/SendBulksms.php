<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Enums\NotificationChannelsType;
use App\Models\Institution;
use App\Models\Message;
use App\Services\Messaging\BulkSmsCompliance;
use App\Traits\ChargesInstitutionMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SendBulksms implements ShouldQueue
{
  use ChargesInstitutionMessage;
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  private int $recipientCount;

  private string $chargeReference;

  public int $tries = 3;

  public array $backoff = [5, 30];

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
  public function handle(?BulkSmsCompliance $compliance = null): void
  {
    $compliance ??= app(BulkSmsCompliance::class);
    $senderId = $compliance->senderId($this->institution);
    $validation = $compliance->validate($this->message, $senderId, $this->to);

    if ($validation->isNotSuccessful()) {
      $this->failMessage($validation->getMessage(), [
        'violations' => $validation['violations'] ?? []
      ]);

      return;
    }

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
      'from' => $validation['sender_id'],
      'to' => $validation['recipients'],
      'gateway' => config('services.bulksms_nigeria.gateway', 'direct-refund')
    ];

    try {
      $res = Http::withToken(config('services.bulksms_nigeria.api-token'))
        ->timeout(config('services.bulksms_nigeria.timeout', 30))
        ->connectTimeout(config('services.bulksms_nigeria.connect-timeout', 10))
        ->withHeaders([
          'Content-Type' => 'application/json',
          'Accept' => 'application/json'
        ])
        ->post(
          rtrim(
            config(
              'services.bulksms_nigeria.base-url',
              'https://www.bulksmsnigeria.com/api/v2'
            ),
            '/'
          ) . '/sms',
          $data
        );
    } catch (ConnectionException $exception) {
      Log::warning('BulkSMS request failed before receiving a response.', [
        'recipient_count' => $this->recipientCount,
        'exception' => $exception::class
      ]);

      throw $exception;
    }

    if ($res->successful() && $this->providerAccepted($res)) {
      $this->messageModel
        ?->fill([
          'status' => MessageStatus::Sent->value,
          'body' => $this->message,
          'sent_at' => now()
        ])
        ->save();

      return;
    }

    $reason = $this->providerFailureReason($res);
    // Log::warning('BulkSMS delivery failed.', [
    //   'http_status' => $res->status(),
    //   'provider_code' => data_get($res->json(), 'code'),
    //   'recipient_count' => $this->recipientCount,
    //   'reason' => $reason
    // ]);

    if ($this->isRetryable($res)) {
      throw new RuntimeException($reason);
    }

    $this->failMessage($reason, [
      'http_status' => $res->status(),
      'provider_code' => data_get($res->json(), 'code')
    ]);
  }

  public function failed(?Throwable $exception): void
  {
    $this->failMessage(
      'SMS delivery failed after the provider retry attempts.',
      ['exception' => $exception?->getMessage()]
    );
  }

  private function providerAccepted($response): bool
  {
    $payload = $response->json();

    return data_get($payload, 'status') === 'success' ||
      data_get($payload, 'data.status') === 'success';
  }

  private function isRetryable($response): bool
  {
    return in_array($response->status(), [408, 425, 429], true) ||
      $response->serverError();
  }

  private function providerFailureReason($response): string
  {
    return (string) (data_get($response->json(), 'message') ??
      (data_get($response->json(), 'error.message') ??
        "BulkSMS returned HTTP {$response->status()}."));
  }

  private function failMessage(string $reason, array $meta = []): void
  {
    if (!$this->messageModel) {
      return;
    }

    $existingMeta = $this->messageModel->meta ?? [];
    $this->messageModel
      ?->fill([
        'status' => MessageStatus::Failed->value,
        'meta' => [
          ...$existingMeta,
          'sms_failure' => [
            'reason' => $reason,
            ...$meta
          ]
        ]
      ])
      ->save();
  }
}
