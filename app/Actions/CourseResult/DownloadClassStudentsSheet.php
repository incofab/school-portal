<?php
namespace App\Actions\CourseResult;

use App\Enums\Sheet\ResultRecordingColumn;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadClassStudentsSheet
{
  private Worksheet $workSheet;
  private Spreadsheet $spreadsheet;
  const COL_STUDENT_ID = 'A';
  const COL_STUDENT_NAME = 'B';
  const COL_STUDENT_CODE = 'C';

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

    return new Xlsx($this->spreadsheet);
  }

  private function setHeaders(int $row)
  {
    $this->workSheet
      ->setCellValue(self::COL_STUDENT_ID . $row, 'Serial')
      ->getColumnDimension(self::COL_STUDENT_ID)
      ->setWidth(10);
    $this->workSheet
      ->setCellValue(self::COL_STUDENT_NAME . $row, 'Student Name')
      ->getColumnDimension(self::COL_STUDENT_NAME)
      ->setWidth(35);
    $this->workSheet
      ->setCellValue(self::COL_STUDENT_CODE . $row, 'Id')
      ->getColumnDimension(self::COL_STUDENT_CODE)
      ->setWidth(25);

    $this->workSheet
      ->getStyle(self::COL_STUDENT_ID)
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $this->workSheet
      ->getStyle(self::COL_STUDENT_NAME)
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $this->workSheet
      ->getStyle(self::COL_STUDENT_CODE)
      ->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $this->workSheet
      ->getStyle($row)
      ->getFont()
      ->setBold(true);
  }

  public function insert(Student $student, int $row)
  {
    $this->workSheet->setCellValue(self::COL_STUDENT_ID . $row, $student->id);

    $this->workSheet->setCellValue(
      self::COL_STUDENT_NAME . $row,
      $student->user->full_name
    );

    $this->workSheet->setCellValue(
      self::COL_STUDENT_CODE . $row,
      $student->code
    );
  }
}
