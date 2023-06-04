<?php
namespace App\Actions\CourseResult;

use App\Enums\Sheet\ResultRecordingColumn;
use App\Models\CourseResult;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadCourseResultSheet
{
  private Worksheet $workSheet;
  private Spreadsheet $spreadsheet;

  function __construct(private Collection $courseResults)
  {
    $this->spreadsheet = new Spreadsheet();
    $this->workSheet = $this->spreadsheet->getActiveSheet();
  }

  public static function run(Collection $courseResults): Xlsx
  {
    $obj = new DownloadCourseResultSheet($courseResults);
    return $obj->execute();
  }

  public function execute(): Xlsx
  {
    $startingRow = 1;
    $row = $startingRow;
    $this->setHeaders($row);
    $row++;

    /** @var \App\Models\CourseResult $courseResults */
    foreach ($this->courseResults as $key => $courseResult) {
      $this->insert($courseResult, $row);
      $row++;
    }

    $this->lockIdColumn($startingRow, $row);

    return new Xlsx($this->spreadsheet);
  }

  private function lockIdColumn(int $startingRow, int $entRow)
  {
    $this->workSheet->protectCells(
      ResultRecordingColumn::StudentID .
        $startingRow .
        ':' .
        ResultRecordingColumn::StudentID .
        $entRow,
      'password'
    );
  }

  private function setHeaders(int $row)
  {
    //Set column width first
    $this->workSheet
      ->getColumnDimension(ResultRecordingColumn::StudentID)
      ->setWidth(10);
    $this->workSheet
      ->getColumnDimension(ResultRecordingColumn::StudentName)
      ->setWidth(25);
    $this->workSheet
      ->getColumnDimension(ResultRecordingColumn::Assesment1Result)
      ->setWidth(10);
    $this->workSheet
      ->getColumnDimension(ResultRecordingColumn::Assesment2Result)
      ->setWidth(10);
    $this->workSheet
      ->getColumnDimension(ResultRecordingColumn::ExamResult)
      ->setWidth(12);

    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentID . $row,
      'Id'
    );

    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentName . $row,
      'Student'
    );
    $this->workSheet->setCellValue(
      ResultRecordingColumn::Assesment1Result . $row,
      'Assesment 1'
    );
    $this->workSheet->setCellValue(
      ResultRecordingColumn::Assesment2Result . $row,
      'Assesment 2'
    );
    $this->workSheet->setCellValue(
      ResultRecordingColumn::ExamResult . $row,
      'Exam'
    );
  }

  public function insert(CourseResult $courseResult, int $row)
  {
    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentID . $row,
      $courseResult->student_id
    );

    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentName . $row,
      $courseResult->student->user->full_name
    );

    $this->workSheet->setCellValue(
      ResultRecordingColumn::Assesment1Result . $row,
      $courseResult->first_assessment
    );

    $this->workSheet->setCellValue(
      ResultRecordingColumn::Assesment2Result . $row,
      $courseResult->second_assessment
    );

    $this->workSheet->setCellValue(
      ResultRecordingColumn::ExamResult . $row,
      $courseResult->exam
    );
  }
}
