<?php
namespace App\Actions;

use App\Enums\ResultRecordingColumn;
use App\Http\Requests\RecordStudentCourseResultRequest;
use App\Models\CourseTeacher;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

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
      $result = $assesment1 + $assesment2 + $exam;

      $data[] = [
        'student_id' => $this->sheetData
          ->getCell(ResultRecordingColumn::StudentID . $row)
          ->getValue(),
        'first_assessment' => $assesment1,
        'last_assessment' => $assesment2,
        'result' => $result
      ];
    }

    $this->validate($data);

    foreach ($data as $result) {
      RecordStudentCourseResult::run(
        [...$this->post, ...$result],
        $this->courseTeacher
      );
    }
  }

  private function validate(array $data)
  {
    $request = new RecordStudentCourseResultRequest($data);
    $validated = $request->validate();
    return $validated;
  }
}
