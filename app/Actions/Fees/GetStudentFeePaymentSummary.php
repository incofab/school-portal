<?php
namespace App\Actions\Fees;

use App\DTO\StudentFeePaymentSummaryDto;
use App\DTO\FeeSummaryDto;
use App\Enums\PaymentInterval;
use App\Models\Classification;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Collection;

class GetStudentFeePaymentSummary
{
  function __construct(
    private Student $student,
    private Classification $classification,
    private string $term,
    private int $academicSessionId
  ) {
  }

  private function getStudentFees()
  {
    $fees = Fee::query()
      ->with('feeCategories')
      ->get()
      ->filter(
        fn(Fee $fee) => $fee->forStudent($this->student, $this->classification)
      );
    return $fees;
  }

  private function getStudentFeePayments(Collection $fees)
  {
    $feePayments = FeePayment::select('fee_payments.*')
      ->join('fees', 'fees.id', 'fee_payments.fee_id')
      ->join('receipts', 'receipts.id', 'fee_payments.receipt_id')
      ->whereIn('fee_payments.fee_id', $fees->pluck('id'))
      ->where(
        fn($q) => $q
          ->where('fee_payments.payable_type', MorphMap::key(User::class))
          ->where('fee_payments.payable_id', $this->student->user_id)
      )
      ->where(function ($qq) {
        $qq
          ->where(
            fn($q) => $q
              ->where('fees.payment_interval', PaymentInterval::Termly)
              ->where('receipts.term', $this->term)
          )
          ->orWhere(
            fn($q) => $q
              ->where('fees.payment_interval', PaymentInterval::Sessional)
              ->where('receipts.academic_session_id', $this->academicSessionId)
          )
          ->orWhere('fees.payment_interval', PaymentInterval::OneTime);
      })
      ->with('receipt')
      ->latest('id')
      ->groupBy('fee_id')
      ->get();
    return $feePayments;
  }

  /**
   * Get student's fee payment summary for a particular receipt type
   */
  function getPaymentSummary($includePaidFees = false)
  {
    $fees = $this->getStudentFees();
    $feePayments = $this->getStudentFeePayments($fees);
    $dto = new StudentFeePaymentSummaryDto($this->student);

    foreach ($fees as $key => $fee) {
      $feePayment = $feePayments
        ->filter(fn($item) => $item->fee_id === $fee->id)
        ->first();

      if (!$feePayment) {
        $dto->updateTotalAmountToPay($fee->amount);
        $dto->addPaymentSummary(
          FeeSummaryDto::new(
            amount_paid: 0,
            amount_remaining: $fee->amount,
            title: $fee->title,
            is_part_payment: false,
            fee_id: $fee->id
          )
        );
        continue;
      }
      if ($includePaidFees) {
        $dto->updateTotalAmountToPay($feePayment->receipt->amount_remaining);
        $dto->addPaymentSummary(
          FeeSummaryDto::new(
            amount_paid: $feePayment->receipt->amount_paid,
            amount_remaining: $feePayment->receipt->amount_remaining,
            title: $fee->title,
            is_part_payment: $feePayment->receipt->amount_remaining > 0,
            fee_id: $fee->id
          )
        );
      }
    }
    return $dto;
  }
}
