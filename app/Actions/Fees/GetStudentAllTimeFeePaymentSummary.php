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
use App\Models\TermResult;
use Illuminate\Database\Eloquent\Collection;

class GetStudentAllTimeFeePaymentSummary
{
  function __construct(
    private Student $student
  ) {
  }
  function run() {
    $termResults = TermResult::query()->where('student_id', $this->student->id)
    ->with('classification')->where('for_mid_term', false)->get();
    $paymentSummary = [];
    foreach ($termResults as $key => $termResult) {
      $paymentSummary[] = (new GetStudentFeePaymentSummary(
        $this->student,
        $termResult->classification,
        $termResult->term->value,
        $termResult->academic_session_id
      ));
    }
    return $paymentSummary;
  }
}
