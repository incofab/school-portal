<?php

namespace App\Support\Payouts\Merchants;

use App\Core\PaystackHelper;
use App\DTO\Payouts\PayoutBatchRequestDto;
use App\DTO\Payouts\PayoutBatchResultDto;
use App\DTO\Payouts\PayoutItemResultDto;
use App\DTO\Payouts\PayoutRequestDto;
use App\Enums\PayoutMerchantType;
use App\Enums\PayoutStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PayoutPaystack extends PayoutMerchant
{
  public function __construct(PayoutMerchantType $merchant)
  {
    parent::__construct($merchant);
    $this->paystack = PaystackHelper::make();
  }

  private PaystackHelper $paystack;

  public function initiateSingle(PayoutRequestDto $request): PayoutItemResultDto
  {
    $recipient = $this->createRecipient($request);
    if (!$recipient) {
      return new PayoutItemResultDto(
        reference: $request->reference,
        status: PayoutStatus::Failed,
        merchantStatus: 'RECIPIENT_FAILED',
        message: 'Unable to create Paystack transfer recipient.'
      );
    }

    $res = $this->paystack->initiateTransfer([
      'source' => 'balance',
      'amount' => (int) round($request->amount * 100),
      'recipient' => $recipient,
      'reference' => $request->reference,
      'reason' => $request->narration,
      'currency' => $request->currency
    ]);

    return $res->isSuccessful()
      ? $this->itemFromBody($request->reference, $res->result ?? [])
      : $this->failedResult($request->reference, $res);
  }

  public function initiateBulk(
    PayoutBatchRequestDto $request
  ): PayoutBatchResultDto {
    $items = array_values($request->items);

    $recipientRes = $this->paystack->createTransferRecipients(
      array_map(
        fn(PayoutRequestDto $item) => $this->recipientPayload($item),
        $items
      )
    );

    if ($recipientRes->isNotSuccessful()) {
      return new PayoutBatchResultDto(
        batchReference: $request->batchReference,
        merchantStatus: 'RECIPIENT_FAILED',
        message: $recipientRes->getMessage(),
        items: array_map(
          fn(PayoutRequestDto $item) => new PayoutItemResultDto(
            reference: $item->reference,
            status: PayoutStatus::Failed,
            merchantStatus: 'RECIPIENT_FAILED',
            message: $recipientRes->getMessage(),
            providerResponse: $recipientRes->toArray()
          ),
          $items
        ),
        providerResponse: $recipientRes->toArray()
      );
    }

    $recipientCodes = collect(Arr::get($recipientRes->result, 'success', []))
      ->pluck('recipient_code')
      ->values();
    $transfers = [];
    foreach ($items as $index => $item) {
      $recipientCode = $recipientCodes->get($index);
      if (!$recipientCode) {
        continue;
      }

      $transfers[] = [
        'amount' => (int) round($item->amount * 100),
        'reference' => $item->reference,
        'reason' => $item->narration,
        'recipient' => $recipientCode
      ];
    }

    $res = $this->paystack->initiateBulkTransfer([
      'source' => 'balance',
      'transfers' => $transfers
    ]);

    if ($res->isNotSuccessful()) {
      return new PayoutBatchResultDto(
        batchReference: $request->batchReference,
        merchantStatus: 'API_ERROR',
        message: $res->getMessage(),
        items: array_map(
          fn(PayoutRequestDto $item) => $this->failedResult(
            $item->reference,
            $res
          ),
          $items
        ),
        providerResponse: $res->toArray()
      );
    }

    $items = array_map(
      fn(array $item) => $this->itemFromBody(
        (string) $item['reference'],
        $item
      ),
      $res->result ?? []
    );

    return new PayoutBatchResultDto(
      batchReference: $request->batchReference,
      merchantStatus: 'QUEUED',
      message: $res->getMessage(),
      items: $items,
      providerResponse: $res->toArray()
    );
  }

  public function verify(string $reference): PayoutItemResultDto
  {
    $res = $this->paystack->verifyTransfer($reference);

    return $res->isSuccessful()
      ? $this->itemFromBody($reference, $res->result ?? [])
      : $this->failedResult($reference, $res);
  }

  private function createRecipient(PayoutRequestDto $request): ?string
  {
    $res = $this->paystack->createTransferRecipient(
      $this->recipientPayload($request)
    );

    return $res->isSuccessful()
      ? Arr::get($res->result, 'recipient_code')
      : null;
  }

  private function recipientPayload(PayoutRequestDto $request): array
  {
    return [
      'type' => 'nuban',
      'name' => $request->beneficiary->accountName,
      'account_number' => $request->beneficiary->accountNumber,
      'bank_code' => $request->beneficiary->bankCode,
      'currency' => $request->currency,
      'description' => $request->narration,
      'metadata' => ['payout_reference' => $request->reference]
    ];
  }

  private function itemFromBody(
    string $reference,
    array $body
  ): PayoutItemResultDto {
    $merchantStatus = strtolower((string) Arr::get($body, 'status', 'unknown'));

    return new PayoutItemResultDto(
      reference: Arr::get($body, 'reference', $reference),
      status: $this->normalizeStatus($merchantStatus),
      merchantStatus: strtoupper($merchantStatus),
      message: Str::limit(
        Arr::get($body, 'gateway_response') ?:
        Arr::get($body, 'failure') ?:
        "Paystack transfer status: {$merchantStatus}",
        250
      ),
      providerReference: Arr::get($body, 'transfer_code'),
      providerResponse: $body
    );
  }
}
