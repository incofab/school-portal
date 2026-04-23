<?php

namespace App\Support\Payments\Processors;

use App\Contracts\Payments\PaymentRecord;
use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\ManualPayment;
use App\Models\PaymentReference;
use App\Models\User;
use App\Support\Payments\Merchants\PaymentMerchant;
use App\Support\Res;
use Exception;
use Illuminate\Support\Facades\DB;

abstract class PaymentProcessor
{
    protected PaymentRecord $paymentReference;

    protected PaymentMerchant $paymentMerchant;

    protected ?User $confirmingUser = null;

    protected function __construct(PaymentRecord $paymentReference)
    {
        $this->paymentReference = $paymentReference;
        $this->paymentMerchant = PaymentMerchant::make(
            $paymentReference->getPaymentMerchant()->value
        );
    }

    protected function verify($verifyAmount = true): Res
    {
        if ($this->paymentReference->getStatus() !== PaymentStatus::Pending) {
            return failRes('Payment already resolved');
        }

        $res = $this->paymentMerchant->verify($this->paymentReference);

        if ($res->isNotSuccessful()) {
            if ($res->is_failed) {
                $this->paymentReference->cancelPayment();
            }

            return $res;
        }

        if ($verifyAmount) {
            $amount = $res->amount;
            if ($amount < $this->paymentReference->getAmount()) {
                return failRes(
                    "Amount paid ($amount) is not equal to the expected amount ({$this->paymentReference->getAmount()})"
                );
            }
        }

        return $res;
    }

    abstract public function processPayment(): Res;

    public function confirmedBy(?User $user): static
    {
        $this->confirmingUser = $user;

        return $this;
    }

    public function processPaymentWithTransaction()
    {
        DB::beginTransaction();

        $ret = $this->processPayment();

        if ($ret->isNotSuccessful()) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        return $ret;
    }

    public static function makeFromReference(string $reference)
    {
        $paymentRef =
          PaymentReference::where('reference', $reference)
              ->with('user')
              ->first() ??
          ManualPayment::where('reference', $reference)
              ->with('user')
              ->firstOrFail();

        return self::make($paymentRef);
    }

    /** @return static */
    public static function make(PaymentRecord $paymentReference)
    {
        $className = self::getProcessorClassName($paymentReference);

        return new $className($paymentReference);
    }

    public static function getProcessorClassName(PaymentRecord $paymentReference)
    {
        if ($paymentReference->getPurpose() === PaymentPurpose::Fee) {
            return FeePaymentProcessor::class;
        } elseif (
            $paymentReference->getPurpose() === PaymentPurpose::WalletFunding
        ) {
            return WalletFundingProcessor::class;
        }

        $paymentable = $paymentReference->getPaymentable();
        if ($paymentable instanceof AdmissionApplication) {
            return AdmissionFormPurchaseProcessor::class;
        }

        $payable = $paymentReference->getPayable();
        if ($payable instanceof AdmissionForm) {
            return AdmissionFormPurchaseProcessor::class;
        }

        throw new Exception(
            'Unknown payment type '.$paymentReference->getPurpose()->value
        );
    }
}
