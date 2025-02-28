<?php

namespace App\Actions\Payments;

use App\Core\PaystackHelper;
use App\Enums\Payments\PaymentStatus;
use App\Models\Institution;
use App\Models\PaymentReference;
use DB;

class ConfirmFeePayment
{
  public function __construct(
    private PaymentReference $paymentReference,
    private Institution $institution
  ) {
  }

  function run()
  {
    $res = PaystackHelper::makeFromInstitution(
      $this->institution
    )->verifyReference($this->paymentReference->reference);

    if ($res->isNotSuccessful()) {
      if ($res->is_failed) {
        $this->paymentReference
          ->fill(['status' => PaymentStatus::Cancelled])
          ->save();
      }
      return $res;
    }

    $feeIds = $this->paymentReference->meta['fee_ids'] ?? [];
    if (empty($feeIds)) {
      return failRes('Fee records not found');
    }

    DB::beginTransaction();
    $this->paymentReference->confirmPayment();

    RecordMultiFeePayments::run(
      [
        'user_id' => $this->paymentReference->user_id,
        'academic_session_id' =>
          $this->paymentReference->meta['academic_session_id'] ?? null,
        'term' => $this->paymentReference->meta['term'] ?? null,
        'method' => null,
        'transaction_reference' => null,
        'fee_ids' => $feeIds
      ],
      $this->institution
    );
    DB::commit();

    return successRes('Payment recorded');
  }
}
