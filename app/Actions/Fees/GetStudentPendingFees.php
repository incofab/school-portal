<?php
namespace App\Actions\Fees;

use App\Enums\Grade;
use App\Models\CourseResult;
use App\Models\Fee;
use App\Models\FeePayment;
use App\Models\ReceiptType;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

class GetStudentPendingFees
{
  function __construct(
    private ReceiptType $receiptType,
    private Student $student
  ) {
  }

  function run()
  {
    return $this->getPendingPayments();
  }

  private function getStudentFees()
  {
    $fees = Fee::where('receipt_type_id', $this->receiptType->id)
      ->where(function ($qq) {
        $qq
          ->where(function ($q) {
            $q->whereNull('classification_group_id')->whereNull(
              'classification_id'
            );
          })
          ->orWhere(function ($q) {
            $q->whereNotNull('classification_group_id')->where(
              'classification_group_id',
              $this->student->classification->classification_group_id
            );
          })
          ->orWhere(function ($q) {
            $q->whereNull('classification_group_id')->where(
              'classification_id',
              $this->student->classification->id
            );
          });
      })
      ->get();
    return $fees;
  }

  private function getPendingPayments()
  {
    $fees = $this->getStudentFees();
    $feePayments = FeePayment::whereIn('fee_id', $fees->pluck('id'))
      ->where('user_id', $this->student->user_id)
      ->latest('id')
      ->groupBy('fee_id')
      ->get();
    $feesToPay = [];
    $totalAmountToPay = 0;
    foreach ($fees as $key => $fee) {
      if (
        $feePayment = $feePayments
          ->filter(fn($item) => $item->fee_id === $fee->id)
          ->first()
      ) {
        if ($feePayment->amount_remaining > 0) {
          $totalAmountToPay += $feePayment->amount_remaining;
          $feesToPay[] = [
            'amount' => $feePayment->amount_remaining,
            'title' => $fee->title,
            'is_part_payment' => true
          ];
        }
      } else {
        $totalAmountToPay += $fee->amount;
        $feesToPay[] = [
          'amount' => $fee->amount,
          'title' => $fee->title,
          'is_part_payment' => false
        ];
      }
    }
    return [$feesToPay, $totalAmountToPay];
  }
}
