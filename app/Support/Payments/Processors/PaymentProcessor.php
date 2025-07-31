<?php
namespace App\Support\Payments\Processors;

use App\Enums\Payments\PaymentPurpose;
use App\Enums\Payments\PaymentStatus;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\PaymentReference;
use App\Support\Payments\Merchants\PaymentMerchant;
use App\Support\Res;
use Exception;
use Illuminate\Support\Facades\DB;

abstract class PaymentProcessor
{
  protected PaymentReference $paymentReference;
  protected PaymentMerchant $paymentMerchant;

  protected function __construct(PaymentReference $paymentReference)
  {
    $this->paymentReference = $paymentReference;
    $this->paymentMerchant = PaymentMerchant::make(
      $paymentReference->merchant->value
    );
  }

  protected function verify($verifyAmount = true): Res
  {
    if ($this->paymentReference->status !== PaymentStatus::Pending) {
      return failRes('Payment already resolved');
    }

    $res = $this->paymentMerchant->verify($this->paymentReference);

    if ($res->isNotSuccessful()) {
      if ($res->is_failed) {
        $this->paymentReference
          ->fill(['status' => PaymentStatus::Cancelled])
          ->save();
      }
      return $res;
    }

    if ($verifyAmount) {
      $amount = $res->amount;
      if ($amount < $this->paymentReference->amount) {
        return failRes(
          "Amount paid ($amount) is not equal to the expected amount ({$this->paymentReference->amount})"
        );
      }
    }

    return $res;
  }

  abstract function processPayment(): Res;

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
    $paymentRef = PaymentReference::where('reference', $reference)
      ->with('user')
      ->firstOrFail();
    return self::make($paymentRef);
  }

  /** @return static */
  public static function make(PaymentReference $paymentReference)
  {
    $className = self::getProcessorClassName($paymentReference);

    return new $className($paymentReference);
  }

  static function getProcessorClassName(PaymentReference $paymentReference)
  {
    if ($paymentReference->purpose === PaymentPurpose::Fee) {
      return FeePaymentProcessor::class;
    } elseif ($paymentReference->purpose === PaymentPurpose::WalletFunding) {
      return WalletFundingProcessor::class;
    }

    $paymentableType = $paymentReference->paymentable_type;
    switch ($paymentableType) {
      case AdmissionApplication::class:
      case 'admission-application':
        return AdmissionFormPurchaseProcessor::class;
    }

    $payableType = $paymentReference->payable_type;
    switch ($payableType) {
      case AdmissionForm::class:
      case 'admission-form':
        return AdmissionFormPurchaseProcessor::class;
      default:
        throw new Exception("Unknown payment type $paymentableType");
        break;
    }
  }
}
