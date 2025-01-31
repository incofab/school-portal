<?php

namespace App\Actions\Users;

use App\Actions\RecordStaff;
use App\Enums\Gender;
use App\Enums\Sheet\StaffRecordingSheetColumn;
use App\Models\Institution;
use App\Models\User;
use DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Validator;

class InsertStaffFromRecordingSheet
{
  private Spreadsheet $spreadsheet;
  private Worksheet $sheetData;

  function __construct(
    private Institution $institution,
    private UploadedFile $file,
    private string $role
  ) {
    $this->spreadsheet = IOFactory::load($this->file->getRealPath());
    $this->sheetData = $this->spreadsheet->getActiveSheet();
  }

  public static function run(
    Institution $institution,
    UploadedFile $file,
    string $role
  ) {
    $obj = new self($institution, $file, $role);
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
      RecordStaff::make($this->institution, [
        ...$teacherData,
        'role' => $this->role
      ])->create();
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
    $this->checkForDuplicateEmail($data);
    $validated = Validator::validate($data, User::generalRule(null, '*.'));

    return $validated;
  }

  private function checkForDuplicateEmail(array $data)
  {
    $emails = [];
    foreach ($data as $key => $item) {
      if (!in_array($item['email'], $emails)) {
        $emails[] = $item['email'];
        continue;
      }
      throw ValidationException::withMessages([
        'email' => 'This data contains duplicate emails'
      ]);
    }
  }
}
