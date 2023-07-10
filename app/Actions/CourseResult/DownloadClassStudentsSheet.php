<?php
namespace App\Actions\CourseResult;

use App\Enums\Sheet\ResultRecordingColumn;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadClassStudentsSheet
{
  private Worksheet $workSheet;
  private Spreadsheet $spreadsheet;

  function __construct(private Collection $students)
  {
    $this->spreadsheet = new Spreadsheet();
    $this->workSheet = $this->spreadsheet->getActiveSheet();
  }

  public static function run(Collection $students): Xlsx
  {
    $obj = new DownloadClassStudentsSheet($students);
    return $obj->execute();
  }

  public function execute(): Xlsx
  {
    $startingRow = 1;
    $row = $startingRow;
    $this->setHeaders($row);
    $row++;

    foreach ($this->students as $key => $student) {
      $this->insert($student, $row);
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
    // $this->workSheet
    //   ->getColumnDimension(ResultRecordingColumn::Assesment1Result)
    //   ->setWidth(10);
    // $this->workSheet
    //   ->getColumnDimension(ResultRecordingColumn::Assesment2Result)
    //   ->setWidth(10);
    // $this->workSheet
    //   ->getColumnDimension(ResultRecordingColumn::ExamResult)
    //   ->setWidth(12);

    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentID . $row,
      'Id'
    );

    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentName . $row,
      'Student'
    );
    // $this->workSheet->setCellValue(
    //   ResultRecordingColumn::Assesment1Result . $row,
    //   'Assesment 1'
    // );
    // $this->workSheet->setCellValue(
    //   ResultRecordingColumn::Assesment2Result . $row,
    //   'Assesment 2'
    // );
    // $this->workSheet->setCellValue(
    //   ResultRecordingColumn::ExamResult . $row,
    //   'Exam'
    // );
  }

  public function insert(Student $student, int $row)
  {
    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentID . $row,
      $student->id
    );

    $this->workSheet->setCellValue(
      ResultRecordingColumn::StudentName . $row,
      $student->user->full_name
    );
  }
}
