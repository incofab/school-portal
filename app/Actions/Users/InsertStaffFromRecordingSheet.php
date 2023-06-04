<?php
namespace App\Actions\Users;

use App\Actions\RecordStaff;
use App\Enums\Gender;
use App\Enums\InstitutionUserType;
use App\Enums\Sheet\StaffRecordingSheetColumn;
use App\Models\User;
use DB;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Validator;

class InsertStaffFromRecordingSheet
{
  private Spreadsheet $spreadsheet;
  private Worksheet $sheetData;

  function __construct(private UploadedFile $file, private string $role)
  {
    $this->spreadsheet = IOFactory::load($this->file->getRealPath());
    $this->sheetData = $this->spreadsheet->getActiveSheet();
  }

  public static function run(UploadedFile $file, string $role)
  {
    $obj = new self($file, $role);
    return $obj->execute();
  }

  public function execute()
  {
    $totalRows = $this->sheetData->getHighestDataRow(
      StaffRecordingSheetColumn::FirstName
    );
    $data = [];
    $rows = range(3, $totalRows);

    foreach ($rows as $row) {
      $data[] = [
        'first_name' => $this->getValue(
          StaffRecordingSheetColumn::FirstName . $row
        ),
        'last_name' => $this->getValue(
          StaffRecordingSheetColumn::LastName . $row
        ),
        'other_names' => $this->getValue(
          StaffRecordingSheetColumn::OtherNames . $row
        ),
        'gender' => $this->getGender($row),
        'email' => $this->getValue(StaffRecordingSheetColumn::Email . $row),
        'phone' => $this->getValue(StaffRecordingSheetColumn::Phone . $row),
        'password' => 'password',
        'password_confirmation' => 'password'
      ];
    }

    $data = $this->validate($data);

    DB::beginTransaction();
    foreach ($data as $teacherData) {
      RecordStaff::create([...$teacherData, 'role' => $this->role]);
    }
    DB::commit();
  }

  private function getGender($row)
  {
    $gender = $this->getValue(StaffRecordingSheetColumn::Gender . $row);
    $gender = strtolower($gender);
    if ($gender === 'm') {
      return Gender::Male;
    } elseif ($gender === 'f') {
      return Gender::Female;
    }
    return Gender::tryFrom($gender ?? '');
  }

  private function getValue(string $column)
  {
    return $this->sheetData->getCell($column)->getValue();
  }

  private function validate(array $data)
  {
    $validated = Validator::validate($data, User::generalRule(null, '*.'));

    return $validated;
  }
}
