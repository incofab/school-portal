<?php
namespace App\Actions\CourseResult;

use App\DTO\SheetColumnIndex;
use App\Enums\Sheet\ResultRecordingColumn;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\CourseResult;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadCourseResult
{
  private Worksheet $workSheet;
  private Spreadsheet $spreadsheet;
  /** @var array<string, SheetColumnIndex> $columnIndex  */
  private array $columnIndex = [];
  /** @var array<string, Assessment> $assessments  */
  private $assessments;

  function __construct(
    private Collection $courseResults,
    private Classification $classification,
    private string $term,
    private bool|null $forMidTerm = false
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
      10
    );

    $this->columnIndex['student_name'] = new SheetColumnIndex(
      $alphabets[++$index],
      'Student',
      25
    );

    foreach ($this->assessments as $key => $assessment) {
      $title = $assessment->columnTitle();
      $this->columnIndex[$title] = new SheetColumnIndex(
        $alphabets[++$index],
        $title,
        12
      );
    }

    $this->columnIndex['exam'] = new SheetColumnIndex(
      $alphabets[++$index],
      'exam',
      15
    );
  }

  public static function run(
    Collection $courseResults,
    Classification $classification,
    string $term,
    bool|null $forMidTerm = false
  ): Xlsx {
    $obj = new self($courseResults, $classification, $term, $forMidTerm);
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
    /** @var ColumnIndex $column */
    foreach ($this->columnIndex as $key => $column) {
      $this->workSheet
        ->setCellValue("{$column->index}{$row}", $column->title)
        ->getColumnDimension($column->index)
        ->setWidth($column->width);
    }
  }

  public function insert(CourseResult $courseResult, int $row)
  {
    $this->workSheet->setCellValue(
      $this->columnIndex['student_id']->index . $row,
      $courseResult->student_id
    );

    $this->workSheet->setCellValue(
      $this->columnIndex['student_name']->index . $row,
      $courseResult->student->user->full_name
    );

    $assessmentValues = $courseResult->assessment_values;
    foreach ($this->assessments as $key => $assessment) {
      $title = $assessment->columnTitle();
      $this->workSheet->setCellValue(
        $this->columnIndex[$title]->index . $row,
        $assessmentValues[$assessment->raw_title] ?? ''
      );
    }

    $this->workSheet->setCellValue(
      $this->columnIndex['exam']->index . $row,
      $courseResult->exam
    );
  }
}
