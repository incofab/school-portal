<?php

namespace App\Support\Payouts\Merchants;

use App\Core\MonnifyHelper;
use App\DTO\Payouts\PayoutBatchRequestDto;
use App\DTO\Payouts\PayoutBatchResultDto;
use App\DTO\Payouts\PayoutItemResultDto;
use App\DTO\Payouts\PayoutRequestDto;
use App\Enums\PayoutMerchantType;
use App\Enums\PayoutStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PayoutMonnify extends PayoutMerchant
{
  public function __construct(PayoutMerchantType $merchant)
  {
    parent::__construct($merchant);
    $this->monnify = MonnifyHelper::make();
  }

  private MonnifyHelper $monnify;

  public function initiateSingle(PayoutRequestDto $request): PayoutItemResultDto
  {
    if (!$this->sourceAccountNumber()) {
      return new PayoutItemResultDto(
        reference: $request->reference,
        status: PayoutStatus::Failed,
        merchantStatus: 'CONFIGURATION_ERROR',
        message: 'Monnify disbursement account number is not configured.'
      );
    }

    $res = $this->monnify->initiateSingleTransfer($this->payload($request));

    return $res->isSuccessful()
      ? $this->itemFromBody($request->reference, $res->result ?? [])
      : $this->failedResult($request->reference, $res);
  }

  public function initiateBulk(
    PayoutBatchRequestDto $request
  ): PayoutBatchResultDto {
    if (!$this->sourceAccountNumber()) {
      return new PayoutBatchResultDto(
        batchReference: $request->batchReference,
        merchantStatus: 'CONFIGURATION_ERROR',
        message: 'Monnify disbursement account number is not configured.',
        items: array_map(
          fn(PayoutRequestDto $item) => new PayoutItemResultDto(
            reference: $item->reference,
            status: PayoutStatus::Failed,
            merchantStatus: 'CONFIGURATION_ERROR',
            message: 'Monnify disbursement account number is not configured.'
          ),
          $request->items
        )
      );
    }

    $res = $this->monnify->initiateBulkTransfer([
      'title' => $request->title,
      'batchReference' => $request->batchReference,
      'narration' => $request->narration,
      'sourceAccountNumber' => $this->sourceAccountNumber(),
      'onValidationFailure' => 'CONTINUE',
      'notificationInterval' => 25,
      'transactionList' => array_map(
        fn(PayoutRequestDto $item) => $this->payload($item, false),
        $request->items
      )
    ]);

    if ($res->isNotSuccessful()) {
      $items = array_map(
        fn(PayoutRequestDto $item) => $this->failedResult(
          $item->reference,
          $res
        ),
        $request->items
      );

      return new PayoutBatchResultDto(
        batchReference: $request->batchReference,
        merchantStatus: 'API_ERROR',
        message: $res->getMessage(),
        items: $items,
        providerResponse: $res->toArray()
      );
    }

    $body = $res->result ?? [];
    $batchStatus = strtoupper(
      (string) Arr::get($body, 'batchStatus', 'UNKNOWN')
    );
    $itemStatus = str_starts_with($batchStatus, 'FAILED')
      ? PayoutStatus::Failed
      : PayoutStatus::Pending;

    return new PayoutBatchResultDto(
      batchReference: Arr::get(
        $body,
        'batchReference',
        $request->batchReference
      ),
      merchantStatus: $batchStatus,
      message: "Monnify batch status: {$batchStatus}",
      items: array_map(
        fn(PayoutRequestDto $item) => new PayoutItemResultDto(
          reference: $item->reference,
          status: $itemStatus,
          merchantStatus: $batchStatus,
          message: "Monnify batch status: {$batchStatus}",
          providerResponse: $body
        ),
        $request->items
      ),
      providerResponse: $body
    );
  }

  public function verify(string $reference): PayoutItemResultDto
  {
    $res = $this->monnify->getSingleTransferStatus($reference);

    return $res->isSuccessful()
      ? $this->itemFromBody($reference, $res->result ?? [])
      : $this->failedResult($reference, $res);
  }

  private function itemFromBody(
    string $reference,
    array $body
  ): PayoutItemResultDto {
    $merchantStatus = strtoupper((string) Arr::get($body, 'status', 'UNKNOWN'));

    return new PayoutItemResultDto(
      reference: Arr::get($body, 'reference', $reference),
      status: $this->normalizeStatus($merchantStatus),
      merchantStatus: $merchantStatus,
      message: Str::limit(
        Arr::get($body, 'transactionDescription') ?:
        Arr::get($body, 'responseMessage') ?:
        "Monnify status: {$merchantStatus}",
        250
      ),
      providerReference: Arr::get($body, 'transactionReference'),
      providerResponse: $body
    );
  }

  private function payload(
    PayoutRequestDto $request,
    bool $withSourceAccount = true
  ): array {
    return [
      'amount' => round($request->amount, 2),
      'reference' => $request->reference,
      'narration' => $request->narration,
      'destinationBankCode' => $request->beneficiary->bankCode,
      'destinationAccountNumber' => $request->beneficiary->accountNumber,
      'destinationAccountName' => $request->beneficiary->accountName,
      'currency' => $request->currency,
      ...$withSourceAccount
        ? ['sourceAccountNumber' => $this->sourceAccountNumber()]
        : []
    ];
  }

  private function sourceAccountNumber(): ?string
  {
    return config('services.monnify.disbursement-account-number');
  }
}
