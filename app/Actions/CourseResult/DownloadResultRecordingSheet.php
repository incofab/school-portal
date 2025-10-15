<?php
namespace App\Actions\CourseResult;

use App\DTO\SheetColumnIndex;
use App\Enums\Sheet\ResultRecordingColumn;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\Student;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadResultRecordingSheet
{
  private Worksheet $workSheet;
  private Spreadsheet $spreadsheet;
  /** @var array<string, SheetColumnIndex> $columnIndex  */
  private array $columnIndex = [];
  /** @var array<string, Assessment> $assessments  */
  private $assessments;

  function __construct(
    private Classification $classification,
    private AcademicSession $academicSession,
    private string $term,
    private bool $forMidTerm
  ) {
    $this->spreadsheet = new Spreadsheet();
    $this->workSheet = $this->spreadsheet->getActiveSheet();

    $this->assessments = Assessment::getAssessments(
      $this->term,
      $this->forMidTerm,
      $this->classification
    );

    $this->setColumnIndexes();
  }

  private function setColumnIndexes()
  {
    $alphabets = range('A', 'Z');
    $index = -1;
    $this->columnIndex['student_id'] = new SheetColumnIndex(
      $alphabets[++$index],
      'ID',
      7
    );

    $this->columnIndex['student_name'] = new SheetColumnIndex(
      $alphabets[++$index],
      'Student',
      30
    );

    foreach ($this->assessments as $key => $assessment) {
      $title = $assessment->columnTitle();
      $this->columnIndex[$title] = new SheetColumnIndex(
        $alphabets[++$index],
        $title,
        20
      );
    }

    $this->columnIndex['exam'] = new SheetColumnIndex(
      $alphabets[++$index],
      'exam',
      15
    );
  }

  public static function run(
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm
  ): Xlsx {
    $obj = new self($classification, $academicSession, $term, $forMidTerm);
    return $obj->execute();
  }

  public function execute(): Xlsx
  {
    $startingRow = 1;
    $row = $startingRow;
    $this->setHeaders($row);
    $row++;

    $students = Student::query()
      ->select('students.*')
      ->join('users', 'students.user_id', 'users.id')
      ->where('classification_id', $this->classification->id)
      ->with('user')
      ->oldest('users.last_name')
      ->get();

    /** @var \App\Models\Student $student */
    foreach ($students as $key => $student) {
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
    /** @var ColumnIndex $column */
    foreach ($this->columnIndex as $key => $column) {
      $this->workSheet
        ->setCellValue("{$column->index}{$row}", $column->title)
        ->getColumnDimension($column->index)
        ->setWidth($column->width);
    }
  }

  public function insert(Student $student, int $row)
  {
    $this->workSheet->setCellValue(
      $this->columnIndex['student_id']->index . $row,
      $student->id
    );

    $this->workSheet->setCellValue(
      $this->columnIndex['student_name']->index . $row,
      "{$student->user->last_name} {$student->user->first_name} {$student->user->other_names}"
    );
  }
}
