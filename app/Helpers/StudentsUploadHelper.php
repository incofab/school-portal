<?php
namespace App\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Models\Institution;
use App\Models\Student;
use App\Models\Grade;

class StudentsUploadHelper
{
  function uploadStudent($files, Institution $institution)
  {
    $ret = $this->uploadFile($files);

    if (!$ret[SUCCESSFUL]) {
      return $ret;
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($ret['full_path']);

    $sheetData = $spreadsheet->getActiveSheet();

    $allStudents = $sheetData->toArray(null, true, true, true);

    $colFirstname = 'A';
    $colLastname = 'B';
    $colPhone = 'C';
    $colEmail = 'D';
    $colAddress = 'E';
    $colGrade = 'F';

    DB::beginTransaction();

    foreach ($allStudents as $row => $student) {
      if ($row == '1' || $student == null) {
        continue;
      }

      $firstName = Arr::get($student, $colFirstname);
      $lastName = Arr::get($student, $colLastname);

      if (empty($firstName) || empty($lastName)) {
        continue;
      }

      $gradeTitle = trim(Arr::get($student, $colGrade));
      $grade = Grade::whereInstitution_id($institution->id)
        ->whereTitle($gradeTitle)
        ->first();

      if ($gradeTitle && !$grade) {
        DB::rollBack();
        return retF(
          "Invalid class ($gradeTitle) supplied for $firstName $lastName"
        );
      }

      $arr = [
        'firstname' => $firstName,
        'lastname' => $lastName,
        'phone' => $this->formatPhoneNo(Arr::get($student, $colPhone)),
        'address' => Arr::get($student, $colAddress),
        'email' => trim(Arr::get($student, $colEmail)),
        'grade_id' => $grade->id ?? null,
        'institution_id' => $institution->id,
        'student_id' => Student::generateStudentID()
      ];

      Student::create($arr);
    }

    DB::commit();

    return retS('All records saved');
  }

  private function uploadFile($files)
  {
    if (!isset($files['content'])) {
      return [SUCCESSFUL => false, MESSAGE => 'Invalid File'];
    }

    // First check if file type is image
    $validExtensions = ['xls', 'xlsx'];

    $maxFilesize = 1000000; // 1mb

    $name = $files['content']['name'];

    $ext = pathinfo($name, PATHINFO_EXTENSION);

    $originalFilename = pathinfo($name, PATHINFO_FILENAME);

    if ($files['content']['size'] > $maxFilesize) {
      return [SUCCESSFUL => false, MESSAGE => 'File greater than 1mb'];
    }

    if (!in_array($ext, $validExtensions)) {
      return [SUCCESSFUL => false, MESSAGE => 'Invalid file Extension'];
    }

    // Check if the file contains errors
    if ($files['content']['error'] > 0) {
      return [
        SUCCESSFUL => false,
        MESSAGE => 'Return Code: ' . $files['content']['error']
      ];
    }

    // Now upload the file
    $filename = "$originalFilename" . '_' . uniqid() . ".$ext";
    $destinationFolder = '../public/files/upload/';
    if (!file_exists($destinationFolder)) {
      mkdir($destinationFolder, 0777, true);
    }
    $destinationPath = $destinationFolder . $filename;

    $tempPath = $files['content']['tmp_name'];

    @move_uploaded_file($tempPath, $destinationPath); // Moving Uploaded file

    return [
      SUCCESSFUL => true,
      MESSAGE => 'File uploaded successfully',
      'filename' => $filename,
      'full_path' => $destinationPath
    ];
  }

  private function formatPhoneNo($phone)
  {
    $phone = str_replace([' ', '-', '  ', '_'], [''], trim($phone));

    if (substr($phone, 0, 3) == '234') {
      return '0' . substr($phone, 3);
    }
    if (substr($phone, 0, 4) == '+234') {
      return '0' . substr($phone, 4);
    }
    if (substr($phone, 0, 1) != '0') {
      return '0' . $phone;
    }
    return $phone;
  }
}
