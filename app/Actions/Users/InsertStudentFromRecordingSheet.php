<?php
namespace App\Actions\Users;

use App\Actions\RecordStudent;
use App\Enums\Gender;
use App\Enums\Sheet\StudentRecordingSheetColumn;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use App\Rules\ValidateExistsRule;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;
use Validator;
use DB;

class InsertStudentFromRecordingSheet
{
  private Spreadsheet $spreadsheet;
  private Worksheet $sheetData;

  function __construct(
    private Institution $institution,
    private UploadedFile $file,
    private Classification $classification
  ) {
    $this->spreadsheet = IOFactory::load($this->file->getRealPath());
    $this->sheetData = $this->spreadsheet->getActiveSheet();
  }

  public static function run(
    Institution $institution,
    UploadedFile $file,
    Classification $classification
  ) {
    $obj = new self($institution, $file, $classification);
    return $obj->execute();
  }

  public function execute()
  {
    $totalRows = $this->sheetData->getHighestDataRow(
      StudentRecordingSheetColumn::FirstName
    );
    $data = [];
    $rows = range(3, $totalRows);

    foreach ($rows as $row) {
      $data[] = [
        'first_name' => $this->getValue(
          StudentRecordingSheetColumn::FirstName . $row
        ),
        'last_name' => $this->getValue(
          StudentRecordingSheetColumn::LastName . $row
        ),
        'other_names' => $this->getValue(
          StudentRecordingSheetColumn::OtherNames . $row
        ),
        'gender' => $this->getGender($row),
        'guardian_phone' => $this->getValue(
          StudentRecordingSheetColumn::GuardianPhone . $row
        ),
        'phone' => $this->getValue(StudentRecordingSheetColumn::Phone . $row),
        'code' => $this->getValue(StudentRecordingSheetColumn::Code . $row),
        'email' => Str::orderedUuid() . '@email.com',
        'password' => 'password',
        'password_confirmation' => 'password'
      ];
    }

    $data = $this->validate($data);

    DB::beginTransaction();
    foreach ($data as $studentData) {
      $studentData['classification_id'] = $this->classification->id;
      RecordStudent::make($this->institution, $studentData)->create();
    }
    DB::commit();
  }

  private function getValue(string $column)
  {
    return $this->sheetData->getCell($column)->getValue();
  }

  private function getGender($row)
  {
    $gender = $this->getValue(StudentRecordingSheetColumn::Gender . $row);
    $gender = strtolower($gender);
    if ($gender === 'm') {
      return Gender::Male;
    } elseif ($gender === 'f') {
      return Gender::Female;
    }
    return Gender::tryFrom($gender ?? '');
  }

  private function validate(array $data)
  {
    $validated = Validator::validate($data, [
      ...User::generalRule(null, '*.'),
      '*.guardian_phone' => ['nullable', 'string'],
      '*.code' => ['sometimes', new ValidateExistsRule(Student::class, 'code')]
    ]);

    return $validated;
  }
}
