<?php
namespace App\Actions\Users;

use App\Enums\Sheet\StudentRecordingSheetColumn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadStudentRecordingSheet
{
  private Worksheet $workSheet;
  private Spreadsheet $spreadsheet;

  function __construct()
  {
    $this->spreadsheet = new Spreadsheet();
    $this->workSheet = $this->spreadsheet->getActiveSheet();
  }

  public static function run(): Xlsx
  {
    $obj = new DownloadStudentRecordingSheet();
    return $obj->execute();
  }

  public function execute(): Xlsx
  {
    $startingRow = 1;
    $this->setTitle($startingRow);
    $row = $startingRow + 1;
    $this->setHeaders($row);

    return new Xlsx($this->spreadsheet);
  }

  private function setTitle(int $row)
  {
    $this->workSheet->mergeCells("A{$row}:D{$row}");
    $this->workSheet->setCellValue("A$row", 'Student\'s Recording Template');
    $this->workSheet
      ->getStyle("A$row")
      ->getFont()
      ->setBold(true);
  }

  private function setHeaders(int $row)
  {
    //Set column width first
    $this->workSheet
      ->getColumnDimension(StudentRecordingSheetColumn::FirstName)
      ->setWidth(25);
    $this->workSheet
      ->getColumnDimension(StudentRecordingSheetColumn::LastName)
      ->setWidth(25);
    $this->workSheet
      ->getColumnDimension(StudentRecordingSheetColumn::OtherNames)
      ->setWidth(24);
    $this->workSheet
      ->getColumnDimension(StudentRecordingSheetColumn::Gender)
      ->setWidth(15);
    $this->workSheet
      ->getColumnDimension(StudentRecordingSheetColumn::GuardianPhone)
      ->setWidth(20);
    $this->workSheet
      ->getColumnDimension(StudentRecordingSheetColumn::Phone)
      ->setWidth(15);

    $this->setPhoneNumberColumn(StudentRecordingSheetColumn::GuardianPhone);
    $this->setPhoneNumberColumn(StudentRecordingSheetColumn::Phone);

    $this->workSheet->setCellValue(
      StudentRecordingSheetColumn::FirstName . $row,
      'First Name'
    );

    $this->workSheet->setCellValue(
      StudentRecordingSheetColumn::LastName . $row,
      'Last Name'
    );
    $this->workSheet->setCellValue(
      StudentRecordingSheetColumn::OtherNames . $row,
      'Other Names'
    );
    $this->workSheet->setCellValue(
      StudentRecordingSheetColumn::Gender . $row,
      'Gender (M/F)'
    );
    $this->workSheet->setCellValue(
      StudentRecordingSheetColumn::GuardianPhone . $row,
      'Guardian Phone'
    );
    $this->workSheet->setCellValue(
      StudentRecordingSheetColumn::Phone . $row,
      'Phone'
    );
  }
  private function setPhoneNumberColumn(string $column)
  {
    $this->workSheet
      ->getStyle($column)
      ->getNumberFormat()
      ->setFormatCode(NumberFormat::FORMAT_TEXT);
  }
}
