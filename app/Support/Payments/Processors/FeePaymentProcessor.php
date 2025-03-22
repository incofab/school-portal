<?php
namespace App\Support\Payments\Processors;

use App\Actions\Payments\RecordMultiFeePayments;
use App\Enums\Payments\PaymentStatus;
use App\Enums\TransactionType;
use App\Support\Fundings\FundingHandler;
use App\Support\Res;
use DB;

class FeePaymentProcessor extends PaymentProcessor
{
  function handleCallback(): Res
  {
    if ($this->paymentReference->status == PaymentStatus::Confirmed) {
      return failRes('Payment already resolved');
    }

    $res = $this->verify();

    if ($res->isNotSuccessful()) {
      return $res;
    }

    $feeIds = $this->paymentReference->meta['fee_ids'] ?? [];
    if (empty($feeIds)) {
      return failRes('Fee records not found');
    }

    DB::beginTransaction();
    $this->paymentReference->confirmPayment();

    $fees = RecordMultiFeePayments::run(
      [
        'user_id' => $this->paymentReference->user_id,
        'academic_session_id' =>
          $this->paymentReference->meta['academic_session_id'] ?? null,
        'term' => $this->paymentReference->meta['term'] ?? null,
        'method' => null,
        'transaction_reference' => null,
        'fee_ids' => $feeIds
      ],
      $this->paymentReference->institution
    );

    FundingHandler::makeFromPaymentRef(
      $this->paymentReference,
      'Fee payment for: ' . $fees->map(fn($item) => $item->title)->join(', ')
    )->fundCreditWallet(
      $this->paymentReference->amount,
      TransactionType::Credit,
      $this->paymentReference
    );
    DB::commit();

    return successRes('Admission form purchased successfully');
  }
}
