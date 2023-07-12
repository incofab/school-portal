<?php
namespace App\Actions\CourseResult;

use App\DTO\ResultSheetColumnIndex;
use App\Http\Requests\RecordCourseResultRequest;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use DB;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Str;
use Validator;

class InsertResultFromRecordingSheet
{
  private Spreadsheet $spreadsheet;
  private Worksheet $sheetData;
  /** @var array<string, ResultSheetInsertColumnIndex> $columnIndex  */
  private array $columnIndex = [];
  /** @var array<string, Assessment> $assessments  */
  private $assessments;

  function __construct(
    private UploadedFile $file,
    private array $post,
    private CourseTeacher $courseTeacher
  ) {
    $this->spreadsheet = IOFactory::load($this->file->getRealPath());
    $this->sheetData = $this->spreadsheet->getActiveSheet();

    $this->assessments = Assessment::query()
      ->forMidTerm($this->post['for_mid_term'] ?? false)
      ->forTerm($this->post['term'])
      ->get();
    $this->setColumnIndexes();
  }

  private function setColumnIndexes()
  {
    $startingRow = 1;
    $totalColumns = $this->sheetData->getHighestDataColumn();

    // We can skip A and B, since they represent student id and name
    $alphabets = range('C', $totalColumns);

    $alphabet = 'A';
    $columnText = $this->sheetData
      ->getCell($alphabet . $startingRow)
      ->getValue();
    $this->columnIndex[$alphabet] = new ResultSheetColumnIndex(
      $alphabet,
      'student_id'
    );

    foreach ($alphabets as $key => $alphabet) {
      $columnText = $this->sheetData
        ->getCell($alphabet . $startingRow)
        ->getValue();
      $this->columnIndex[$alphabet] = new ResultSheetColumnIndex(
        $alphabet,
        $columnText
      );
    }
  }

  public static function run(
    UploadedFile $file,
    array $post,
    CourseTeacher $courseTeacher
  ) {
    $obj = new self($file, $post, $courseTeacher);
    return $obj->execute();
  }

  public function execute()
  {
    $totalRows = $this->sheetData->getHighestDataRow();

    $data = [];
    $rows = range(2, $totalRows);

    foreach ($rows as $row) {
      $rowData = [];
      $assessmentRecords = [];
      foreach ($this->columnIndex as $alphabet => $column) {
        $value = $this->sheetData->getCell($alphabet . $row)->getValue();
        if (Str::startsWith($column->title, Assessment::PREFIX)) {
          $filteredTitle = substr($column->title, strlen(Assessment::PREFIX));
          $assessmentRecords[$filteredTitle] = $value;
        } else {
          $rowData[$column->title] = $value;
        }
      }
      $rowData['ass'] = $assessmentRecords;
      $data[] = $rowData;
    }

    // info(json_encode($data, JSON_PRETTY_PRINT));
    // info('***********************');
    $data = $this->validate($data);
    // info(json_encode($data, JSON_PRETTY_PRINT));
    // dd('dmsknksd');
    DB::beginTransaction();
    foreach ($data as $result) {
      RecordCourseResult::run(
        [...$this->post, ...$result],
        $this->courseTeacher
      );
    }
    DB::commit();

    EvaluateCourseResultForClass::run(
      $this->courseTeacher->classification,
      $this->courseTeacher->course_id,
      $this->post['academic_session_id'],
      $this->post['term'],
      $this->post['for_mid_term']
    );
  }

  private function validate(array $data)
  {
    $validated = Validator::validate(
      $data,
      (new RecordCourseResultRequest())->resultRule(
        $this->courseTeacher,
        $data,
        '*.'
      )
    );
    return $validated;
  }
}
