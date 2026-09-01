<?php

namespace App\Actions\Payments;

use App\DTO\PaymentReferenceDto;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\Student;
use App\Support\Payments\Merchants\PaymentMerchant;
use Illuminate\Validation\ValidationException;

class InitializePublicStudentFeePayment
{
  public function __construct(
    private Institution $institution,
    private Student $student,
    private array $data
  ) {
  }

  public static function make(
    Institution $institution,
    Student $student,
    array $data
  ) {
    return new self($institution, $student, $data);
  }

  public function run(): array
  {
    $fee = Fee::query()
      ->whereKey($this->data['fee_id'])
      ->where('institution_id', $this->institution->id)
      ->with('feeCategories')
      ->firstOrFail();

    abort_unless(
      $fee->forStudent($this->student, $this->student->classification),
      403,
      'This fee is not assigned to the student.'
    );

    $receipt = FeePaymentHandler::getReceipt($fee, $this->student->user);
    $amountRemaining = $receipt?->amount_remaining ?? $fee->amount;

    if ($amountRemaining < 1) {
      throw ValidationException::withMessages([
        'fee_id' => 'This fee has already been paid.'
      ]);
    }

    if ((float) $this->data['amount'] > $amountRemaining) {
      throw ValidationException::withMessages([
        'amount' =>
          'The amount cannot be more than the outstanding balance of ' .
          number_format($amountRemaining, 2)
      ]);
    }

    $merchant = $this->data['merchant'];
    $reference = ManualPayment::generateReference();
    $redirectUrl =
      $merchant === PaymentMerchantType::Manual->value
        ? route('institutions.manual-payments.show', [
          $this->institution,
          $reference
        ])
        : route('public.student-fee-payment', [
          $this->institution->code,
          $this->student->code
        ]);

    $paymentReference = new PaymentReferenceDto(
      institution_id: $this->institution->id,
      merchant: $merchant,
      payable: $this->student->user,
      paymentable: $fee,
      amount: (float) $this->data['amount'],
      purpose: PaymentPurpose::Fee,
      user_id: $this->student->user_id,
      reference: $reference,
      redirect_url: $redirectUrl,
      meta: [
        'student_code' => $this->student->code,
        'public_payment' => true,
        'academic_session_id' => $fee->academic_session_id,
        'term' => $fee->term?->value ?? $fee->term
      ]
    );

    [$res] = PaymentMerchant::make($merchant)->init($paymentReference);
    abort_unless($res->isSuccessful(), 403, $res->getMessage());

    return $res->toArray();
  }
}
