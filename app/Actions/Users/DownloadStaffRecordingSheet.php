<?php
namespace App\Actions\Users;

use App\Enums\Sheet\StaffRecordingSheetColumn;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadStaffRecordingSheet
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
    $obj = new DownloadStaffRecordingSheet();
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
    $this->workSheet->setCellValue("A$row", 'Teacher\'s Recording Template');
    $this->workSheet
      ->getStyle("A$row")
      ->getFont()
      ->setBold(true);
  }

  private function setHeaders(int $row)
  {
    //Set column width first
    $this->workSheet
      ->getColumnDimension(StaffRecordingSheetColumn::FirstName)
      ->setWidth(25);
    $this->workSheet
      ->getColumnDimension(StaffRecordingSheetColumn::LastName)
      ->setWidth(25);
    $this->workSheet
      ->getColumnDimension(StaffRecordingSheetColumn::OtherNames)
      ->setWidth(24);
    $this->workSheet
      ->getColumnDimension(StaffRecordingSheetColumn::Gender)
      ->setWidth(15);
    $this->workSheet
      ->getColumnDimension(StaffRecordingSheetColumn::Email)
      ->setWidth(20);
    $this->workSheet
      ->getColumnDimension(StaffRecordingSheetColumn::Phone)
      ->setWidth(15);

    $this->setPhoneNumberColumn(StaffRecordingSheetColumn::Phone);

    $this->workSheet->setCellValue(
      StaffRecordingSheetColumn::FirstName . $row,
      'First Name'
    );

    $this->workSheet->setCellValue(
      StaffRecordingSheetColumn::LastName . $row,
      'Last Name'
    );
    $this->workSheet->setCellValue(
      StaffRecordingSheetColumn::OtherNames . $row,
      'Other Names'
    );
    $this->workSheet->setCellValue(
      StaffRecordingSheetColumn::Gender . $row,
      'Gender (M/F)'
    );
    $this->workSheet->setCellValue(
      StaffRecordingSheetColumn::Email . $row,
      'Email'
    );
    $this->workSheet->setCellValue(
      StaffRecordingSheetColumn::Phone . $row,
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
