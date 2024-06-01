<?php
namespace App\Actions;

use App\DTO\SheetColumnIndex;
use App\Models\Classification;
use App\Models\ReceiptType;
use App\Models\Student;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PrepareFeePaymentRecordingSheet
{
  public const COL_STUDENT_ID = 'A';
  public const COL_STUDENT_NAME = 'B';

  private $fees;
  function __construct(
    private Classification $classification,
    private ReceiptType $receiptType
  ) {
    $this->fees = $receiptType->fees()->get();
  }

  public function generateSheet(): Xlsx
  {
    $spreadsheet = new Spreadsheet();
    $workSheet = $spreadsheet->getActiveSheet();
    $students = $this->classification
      ->students()
      ->with('user')
      ->get();

    $row = 1;
    $this->setHeaders($workSheet, $row);
    $row++;
    foreach ($students as $key => $student) {
      $this->insert($workSheet, $student, $row);
      $row++;
    }

    return new Xlsx($spreadsheet);
  }

  private function getColumnSheetIndex()
  {
    $initialColumns = [
      new SheetColumnIndex(self::COL_STUDENT_ID, 'Student Id', 10),
      new SheetColumnIndex(self::COL_STUDENT_NAME, 'Names', 25)
    ];
    $letters = range('C', 'Z');
    $arr = [];
    for ($i = 0; $i < $this->fees->count(); $i++) {
      /** @var Fee $fee */
      $fee = $this->fees[$i];
      $arr[] = new SheetColumnIndex($letters[$i], $fee->title, 20);
    }
    return array_merge($initialColumns, $arr);
  }

  private function setHeaders(Worksheet $workSheet, int $row)
  {
    $workSheet
      ->getStyle($row)
      ->getFont()
      ->setBold(true);

    $workSheet
      ->getStyle('B')
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $sheetIndices = $this->getColumnSheetIndex();

    foreach ($sheetIndices as $key => $sheetIndex) {
      $workSheet
        ->setCellValue("{$sheetIndex->index}$row", 'Name')
        ->getColumnDimension($sheetIndex->title)
        ->setWidth($sheetIndex->width);
    }
  }

  private function insert(Worksheet $workSheet, Student $student, int $row)
  {
    $workSheet
      ->setCellValue(self::COL_STUDENT_ID . $row, $student->code)
      ->setCellValue(self::COL_STUDENT_NAME . $row, $student->user->full_name);
  }
}
