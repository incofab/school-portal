<?php
namespace App\Actions\CourseResult;

use App\Enums\Sheet\ResultRecordingColumn;
use App\Http\Requests\RecordCourseResultRequest;
use App\Models\CourseTeacher;
use DB;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Validator;

class InsertResultFromRecordingSheet
{
  private Spreadsheet $spreadsheet;
  private Worksheet $sheetData;

  function __construct(
    private UploadedFile $file,
    private array $post,
    private CourseTeacher $courseTeacher
  ) {
    $this->spreadsheet = IOFactory::load($this->file->getRealPath());
    $this->sheetData = $this->spreadsheet->getActiveSheet();
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
      $assesment1 = (int) $this->sheetData
        ->getCell(ResultRecordingColumn::Assesment1Result . $row)
        ->getValue();
      $assesment2 = (int) $this->sheetData
        ->getCell(ResultRecordingColumn::Assesment2Result . $row)
        ->getValue();
      $exam = (int) $this->sheetData
        ->getCell(ResultRecordingColumn::ExamResult . $row)
        ->getValue();

      $data[] = [
        'student_id' => (int) $this->sheetData
          ->getCell(ResultRecordingColumn::StudentID . $row)
          ->getValue(),
        'first_assessment' => $assesment1,
        'second_assessment' => $assesment2,
        'exam' => $exam
      ];
    }

    $data = $this->validate($data);
    // info([...$this->post, ...$data]);
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
      $this->post['term']
    );
  }

  private function validate(array $data)
  {
    $validated = Validator::validate(
      $data,
      RecordCourseResultRequest::resultRule($data, '*.')
    );
    return $validated;
  }
}
