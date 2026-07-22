<?php

namespace App\Actions\Payments;

use App\Enums\Payments\PaymentStatus;
use App\Models\ManualPayment;
use App\Models\User;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\FinancialActivityLogger;
use App\Support\Payments\Processors\PaymentProcessor;
use App\Support\Res;
use Illuminate\Support\Facades\DB;

class ManualPaymentHandler
{
  public function confirm(ManualPayment $manualPayment, User $staff): Res
  {
    $res = ModelAudit::withoutAuditingFor(
      ManualPayment::class,
      fn() => PaymentProcessor::make(
        $manualPayment->load('institution', 'user', 'payable', 'paymentable')
      )
        ->confirmedBy($staff)
        ->processPaymentWithTransaction()
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
    $res = ModelAudit::withoutAuditingFor(
      ManualPayment::class,
      function () use ($manualPayment, $staff, $reviewNote) {
        return DB::transaction(function () use (
          $manualPayment,
          $staff,
          $reviewNote
        ) {
          $lockedManualPayment = $manualPayment->freshWithLockForUpdate();

          if ($lockedManualPayment->status !== PaymentStatus::Pending) {
            return failRes('Payment already resolved');
          }

          $lockedManualPayment->cancelPayment($staff, $reviewNote);

          return successRes('Manual payment rejected', [
            'manualPayment' => $lockedManualPayment
          ]);
        });
      }
    );

    if ($res->isSuccessful()) {
      app(FinancialActivityLogger::class)->manualPaymentReviewed(
        $manualPayment->refresh(),
        $staff,
        false
      );
    }

    return $res;
  }
}
