<?php
namespace App\Actions;

use App\Models\Institution;
use App\Models\ReceiptType;
use App\Models\Student;
use App\Models\Fee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InsertFeePaymentFromSheet
{
  /**
   * @param Institution $institution
   * @param ReceiptType $receiptType
   * @param ?AcademicSession $academicSession
   * @param ?string|null $term
   */
  function __construct(
    private Institution $institution,
    private ReceiptType $receiptType,
    private $academicSession,
    private $term
  ) {
  }

  public function upload(UploadedFile $file)
  {
    $sheetData = IOFactory::load($file->getRealPath())->getActiveSheet();
    $feeColumnMap = $this->getFeeColumnMap($sheetData);

    $totalRows = $sheetData->getHighestDataRow(
      PrepareFeePaymentRecordingSheet::COL_STUDENT_ID
    );
    $rows = range(2, $totalRows);

    foreach ($rows as $row) {
      $this->recordSheetRow($sheetData, $row, $feeColumnMap);
    }
  }

  /**
   * @param array<string, Fee> $feeColumnMap
   */
  private function recordSheetRow(Worksheet $sheetData, int $row, $feeColumnMap)
  {
    foreach ($feeColumnMap as $column => $fee) {
      $value = floatval($sheetData->getCell($column . $row)->getValue());
      $studentCode = floatval(
        $sheetData
          ->getCell(PrepareFeePaymentRecordingSheet::COL_STUDENT_ID . $row)
          ->getValue()
      );

      $student = Student::query()
        ->where('code', $studentCode)
        ->first();
      if (!$student || $value < 1) {
        continue;
      }

      RecordFeePayment::run(
        [
          'user_id' => $student->user_id,
          'fee_id' => $fee->id,
          'term' => $this->term,
          'academic_session_id' => $this->academicSession?->id,
          'amount' => $value,
          'reference' => Str::orderedUuid()
        ],
        $this->institution
      );
    }
  }

  /**
   * @return array<string, Fee>
   */
  private function getFeeColumnMap(Worksheet $sheetData)
  {
    $titleRow = 1;
    $highestColumn = $sheetData->getHighestDataColumn($titleRow);
    $columns = range('C', $highestColumn);
    $feeColumnMap = [];
    foreach ($columns as $key => $column) {
      $title = $sheetData->getCell($column . $titleRow)->getValue();
      $fee = $this->receiptType
        ->fees()
        ->where('title', $title)
        ->firstOrFail();
      $feeColumnMap[$column] = $fee;
    }
    return $feeColumnMap;
  }
}
