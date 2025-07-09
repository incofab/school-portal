<?php
namespace App\Actions\Fees;

use App\Actions\GenericExport;
use App\DTO\FeeSummaryDto;
use App\DTO\StudentFeePaymentSummaryDto;
use App\Models\Classification;
use App\Models\Fee;
use Illuminate\Support\Collection;

class GetClassFeePaymentSummary
{
  function __construct(
    private Classification $classification,
    private string $term,
    private int $academicSessionId
  ) {
  }

  function run()
  {
    $this->classification->load('students.user');
    $feeSummaries = [];
    $students = $this->classification->students;
    foreach ($students as $key => $student) {
      $summary = (new GetStudentFeePaymentSummary(
        $student,
        $this->classification,
        $this->term,
        $this->academicSessionId
      ))->getPaymentSummary(true);
      $feeSummaries[] = $summary;
    }
    $fees = $this->getRelatedFees($feeSummaries);
    return [$feeSummaries, $fees];
  }

  /**
   * @param StudentFeePaymentSummaryDto[] $studentsFeeSummary
   * @param Fee[] $fees
   * @return array {
   *  student: Student,
   *  fees: array<string, string>
   * }
   */
  function formateData(array $studentsFeeSummary, array $fees)
  {
    $formatted = [];
    foreach ($studentsFeeSummary as $key => $summary) {
      $studentFees = $summary->getPaymentSummaries();
      $formattedFees = [];
      foreach ($fees as $key => $fee) {
        $summary = collect($studentFees)->first(
          fn(FeeSummaryDto $item) => $item->fee_id === $fee->id
        );
        $formattedFees["{$fee->title}({$fee->amount})"] = $summary
          ? $summary->amount_paid
          : null;
      }
      $formatted[] = [
        'student' => $summary->getStudent(),
        'fees' => $formattedFees
      ];
    }
    return $formatted;
  }

  /**
   * @param array<int, StudentFeePaymentSummaryDto> $studentsFeeSummary
   */
  private function getRelatedFees(array $studentsFeeSummary)
  {
    $allFees = Fee::query()
      ->where('academic_session_id', $this->academicSessionId)
      ->where('term', $this->term)
      ->get();
    $fees = $this->getClassFees();
    $feeIds = [];
    foreach ($studentsFeeSummary as $key => $summary) {
      foreach ($summary->getPaymentSummaries() as $key => $feeData) {
        if (!in_array($feeData->fee_id, $feeIds)) {
          $feeIds[] = $feeData->fee_id;
        }
      }
    }
    foreach ($fees as $key => $fee) {
      if (!in_array($fee->id, $feeIds)) {
        $feeIds[] = $fee->id;
      }
    }
    return $allFees->filter(fn(Fee $fee) => in_array($fee->id, $feeIds));
  }

  private function getClassFees()
  {
    return Fee::query()
      ->with('feeCategories')
      ->get()
      ->filter(fn(Fee $fee) => $fee->forClass($this->classification));
  }

  /**
   * @param Fee[] $fees
   * @param StudentFeePaymentSummaryDto[] $feeSummaries
   */
  static function downloadAsExcel(array|Collection $fees, array $feeSummaries)
  {
    function formatFee(Fee $fee, StudentFeePaymentSummaryDto $feeSummaries)
    {
      $summary = collect($feeSummaries->getPaymentSummaries())->first(
        fn($item) => $item->fee_id === $fee->id
      );

      $feeAmount = number_format($fee->amount, 2);
      return [
        "{$fee->title} ($feeAmount)" => number_format(
          $summary->amount_paid ?? 0,
          2
        )
      ];
    }

    $data = collect($feeSummaries)->map(
      fn($item) => [
        'student' => $item->getStudent()->user->full_name,
        ...collect($fees)->mapWithKeys(fn($fee) => formatFee($fee, $item))
      ]
    );
    return (new GenericExport($data, 'questions.xlsx'))->download();
  }
}
