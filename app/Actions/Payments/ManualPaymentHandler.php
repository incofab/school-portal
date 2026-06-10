<?php

namespace App\Actions\Payments;

use App\Enums\Payments\PaymentStatus;
use App\Models\ManualPayment;
use App\Models\User;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\FinancialActivityLogger;
use App\Support\Payments\Processors\PaymentProcessor;
use App\Support\Res;

class ManualPaymentHandler
{
  public function confirm(ManualPayment $manualPayment, User $staff): Res
  {
    if ($manualPayment->status !== PaymentStatus::Pending) {
      return failRes('Payment already resolved');
    }

    $res = ModelAudit::withoutAuditingFor(
      ManualPayment::class,
      fn() => PaymentProcessor::make(
        $manualPayment->load('institution', 'user', 'payable', 'paymentable')
      )
        ->confirmedBy($staff)
        ->processPayment()
    );

    if ($res->isSuccessful()) {
      app(FinancialActivityLogger::class)->manualPaymentReviewed(
        $manualPayment->refresh(),
        $staff,
        true
      );
    }

    return $res;
  }

  public function reject(
    ManualPayment $manualPayment,
    User $staff,
    ?string $reviewNote = null
  ): Res {
    if ($manualPayment->status !== PaymentStatus::Pending) {
      return failRes('Payment already resolved');
    }

    ModelAudit::withoutAuditingFor(ManualPayment::class, function () use (
      $manualPayment,
      $staff,
      $reviewNote
    ) {
      $manualPayment->cancelPayment($staff, $reviewNote);
    });

    app(FinancialActivityLogger::class)->manualPaymentReviewed(
      $manualPayment->refresh(),
      $staff,
      false
    );

    return successRes('Manual payment rejected');
  }
}
