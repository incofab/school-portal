<?php

namespace App\Support\Payments\Merchants;

use App\Contracts\Payments\PaymentRecord;
use App\DTO\PaymentReferenceDto;
use App\Enums\Payments\PaymentMethod;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\ManualPayment;
use App\Support\Res;

class PaymentManual extends PaymentMerchant
{
  public function init(
    PaymentReferenceDto $paymentReferenceDto,
    bool $generateReferenceOnly = false
  ) {
    abort_if(
      in_array($paymentReferenceDto->purpose, [PaymentPurpose::WalletFunding]),
      400,
      'Manual payments cannot be used for wallet funding'
    );

    $manualPayment = ManualPayment::query()->firstOrCreate(
      ['reference' => $paymentReferenceDto->reference],
      [
        'institution_id' => $paymentReferenceDto->institution_id,
        'user_id' => $paymentReferenceDto->user_id,
        'bank_account_id' =>
          $paymentReferenceDto->manualData['bank_account_id'] ?? null,
        'payable_id' => $paymentReferenceDto->getPayable()?->id,
        'payable_type' => $paymentReferenceDto->getPayable()?->getMorphClass(),
        'paymentable_id' => $paymentReferenceDto->getPaymentable()?->id,
        'paymentable_type' => $paymentReferenceDto
          ->getPaymentable()
          ?->getMorphClass(),
        'amount' => $paymentReferenceDto->amount,
        'purpose' => $paymentReferenceDto->purpose,
        'method' => PaymentMethod::Bank,
        'status' => PaymentStatus::Pending,
        'depositor_name' =>
          $paymentReferenceDto->manualData['depositor_name'] ?? null,
        'paid_at' => $paymentReferenceDto->manualData['paid_at'] ?? null,
        'proof_path' => $paymentReferenceDto->manualData['proof_path'] ?? null,
        'proof_url' => $paymentReferenceDto->manualData['proof_url'] ?? null,
        'meta' => $paymentReferenceDto->meta,
        'payload' => $paymentReferenceDto->manualData
      ]
    );

    $ret = successRes(
      'Your manual payment has been recorded and is awaiting confirmation from the institution.',
      [
        'manualPayment' => $manualPayment,
        'reference' => $manualPayment->reference,
        'amount' => $manualPayment->amount,
        'redirect_url' => $paymentReferenceDto->redirect_url
      ]
    );

    return [$ret, $manualPayment];
  }

  public function verify(PaymentRecord $paymentRecord): Res
  {
    return $paymentRecord->getStatus() === PaymentStatus::Pending
      ? successRes('', ['amount' => $paymentRecord->getAmount()])
      : failRes('Payment already resolved');
  }
}
