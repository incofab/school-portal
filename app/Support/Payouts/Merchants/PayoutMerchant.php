<?php

namespace App\Support\Payouts\Merchants;

use App\DTO\Payouts\PayoutBatchRequestDto;
use App\DTO\Payouts\PayoutBatchResultDto;
use App\DTO\Payouts\PayoutRequestDto;
use App\DTO\Payouts\PayoutItemResultDto;
use App\Enums\PayoutMerchantType;
use App\Enums\PayoutStatus;
use App\Support\Res;
use Illuminate\Support\Str;

abstract class PayoutMerchant
{
  public function __construct(protected PayoutMerchantType $merchant)
  {
  }

  abstract public function initiateSingle(
    PayoutRequestDto $request
  ): PayoutItemResultDto;

  abstract public function initiateBulk(
    PayoutBatchRequestDto $request
  ): PayoutBatchResultDto;

  abstract public function verify(string $reference): PayoutItemResultDto;

  public function merchant(): PayoutMerchantType
  {
    return $this->merchant;
  }

  protected function normalizeStatus(string $status): PayoutStatus
  {
    $status = strtolower($status);

    return match ($status) {
      'success', 'successful', 'completed' => PayoutStatus::Successful,
      'failed',
      'failure',
      'reversed',
      'expired',
      'cancelled',
      'canceled'
        => PayoutStatus::Failed,
      'pending',
      'processing',
      'in_progress',
      'awaiting_processing',
      'pending_authorization',
      'otp',
      'otp_email_dispatch_failed'
        => PayoutStatus::Pending,
      default => PayoutStatus::Unknown
    };
  }

  protected function failedResult(
    string $reference,
    Res $res,
    string $merchantStatus = 'API_ERROR'
  ): PayoutItemResultDto {
    return new PayoutItemResultDto(
      reference: $reference,
      status: PayoutStatus::Unknown,
      merchantStatus: $merchantStatus,
      message: Str::limit($res->getMessage() ?: 'Payout request failed.', 250),
      providerResponse: $res->toArray()
    );
  }

  public static function make(?string $merchant = null): self
  {
    $type = $merchant
      ? PayoutMerchantType::from($merchant)
      : PayoutMerchantType::default();

    return match ($type) {
      PayoutMerchantType::Monnify => new PayoutMonnify($type),
      PayoutMerchantType::Paystack => new PayoutPaystack($type)
    };
  }
}
