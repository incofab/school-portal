<?php

namespace App\Actions\Payments;

use App\Enums\Payments\PaymentStatus;
use App\Models\ManualPayment;
use App\Models\User;
use App\Support\Payments\Processors\PaymentProcessor;
use App\Support\Res;

class ManualPaymentHandler
{
    public function confirm(ManualPayment $manualPayment, User $staff): Res
    {
        if ($manualPayment->status !== PaymentStatus::Pending) {
            return failRes('Payment already resolved');
        }

        return PaymentProcessor::make(
            $manualPayment->load('institution', 'user', 'payable', 'paymentable')
        )
            ->confirmedBy($staff)
            ->processPayment();
    }

    public function reject(
        ManualPayment $manualPayment,
        User $staff,
        ?string $reviewNote = null
    ): Res {
        if ($manualPayment->status !== PaymentStatus::Pending) {
            return failRes('Payment already resolved');
        }

        $manualPayment->cancelPayment($staff, $reviewNote);

        return successRes('Manual payment rejected');
    }
}
