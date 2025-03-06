<?php
namespace App\Actions\Fees;

use App\Enums\Grade;
use App\Enums\PaymentInterval;
use App\Models\Classification;
use App\Models\CourseResult;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\ReceiptType;
use App\Models\Student;
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

  private function getStudentFees(ReceiptType|null $receiptType = null)
  {
    $fees = Fee::query()
      ->forReceiptType($receiptType)
      ->forClass($this->classification)
      ->get();
    return $fees;
  }

  private function getStudentFeePayments(Collection $fees)
  {
    $feePayments = FeePayment::select('fee_payments.*')
      ->join('fees', 'fees.id', 'fee_payments.fee_id')
      ->whereIn('fee_payments.fee_id', $fees->pluck('id'))
      ->where('fee_payments.user_id', $this->student->user_id)
      ->where(function ($qq) {
        $qq
          ->where(
            fn($q) => $q
              ->where('fees.payment_interval', PaymentInterval::Termly)
              ->where('fee_payments.term', $this->term)
          )
          ->orWhere(
            fn($q) => $q
              ->where('fees.payment_interval', PaymentInterval::Sessional)
              ->where(
                'fee_payments.academic_session_id',
                $this->academicSessionId
              )
          )
          ->orWhere('fees.payment_interval', PaymentInterval::OneTime);
      })
      ->latest('id')
      ->groupBy('fee_id')
      ->get();
    return $feePayments;
  }

  /**
   * Get student's fee payment summary for a particular receipt type
   */
  function getPaymentSummary(
    ReceiptType|null $receiptType = null,
    $includePaidFees = false
  ) {
    $fees = $this->getStudentFees($receiptType);
    $feePayments = $this->getStudentFeePayments($fees);
    $feesToPay = [];
    $totalAmountToPay = 0;
    $totalAmountOfTheReceiptType = 0; //This is the total value of all the fees in the ReceiptType.

    foreach ($fees as $key => $fee) {
      $feePayment = $feePayments
        ->filter(fn($item) => $item->fee_id === $fee->id)
        ->first();

      $totalAmountOfTheReceiptType += $fee->amount;

      if (!$feePayment) {
        $totalAmountToPay += $fee->amount;
        $feesToPay[] = [
          'amount_paid' => 0,
          'amount_remaining' => $fee->amount,
          'title' => $fee->title,
          'is_part_payment' => false
        ];
        continue;
      }

      if ($feePayment->amount_remaining > 0 || $includePaidFees) {
        $totalAmountToPay += $feePayment->amount_remaining;
        $feesToPay[] = [
          'amount_paid' => $fee->amount - $feePayment->amount_remaining,
          'amount_remaining' => $feePayment->amount_remaining,
          'title' => $fee->title,
          'is_part_payment' => $feePayment->amount_remaining > 0
        ];
      }
    }
    return [$feesToPay, $totalAmountToPay, $totalAmountOfTheReceiptType];
  }

  /**
   * Get student fee payment summary for all the receipt type in a particular class, term and session
   */
  function getStudentReceiptPaymentSummary(Collection $receiptTypes)
  {
    $processedReceiptTypes = [];
    foreach ($receiptTypes as $key => $receiptType) {
      [
        $feesToPay,
        $totalAmountToPay,
        $totalAmountOfTheReceiptType
      ] = $this->getPaymentSummary($receiptType);

      $processedReceiptTypes[] = [
        'receipt_type' => $receiptType,
        'fees_to_pay' => $feesToPay,
        'total_amount_to_pay' => $totalAmountToPay,
        'total_amount_of_the_receipt_type' => $totalAmountOfTheReceiptType
      ];
    }
    return $processedReceiptTypes;
  }
}
