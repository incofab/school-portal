<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Institution;
use Illuminate\Support\Str;
use App\Actions\RecordStudent;
use App\Actions\RecordGuardian;
use Illuminate\Support\Facades\DB;
use App\Models\AdmissionApplication;
use Illuminate\Support\Facades\Storage;

class HandleAdmission
{
  private Institution $institution;

  public function __construct()
  {
    $this->institution = currentInstitution();
  }

  public static function make()
  {
    return new self();
  }

  public function admitStudent(
    AdmissionApplication $admissionApplication,
    $data
  ) {
    $guardians = $admissionApplication->applicationGuardians()->get();
    $sourcePath = $admissionApplication->photo;

    $parts = explode('/', $sourcePath);
    $fileName = end($parts);

    $destinationPath = 'avatars/users/' . $fileName;

    Storage::disk('s3_public')->move($sourcePath, $destinationPath);
    $destinationUrl = Storage::disk('s3_public')->url($destinationPath);

    DB::beginTransaction();
    $student = RecordStudent::make($this->institution, [
      'classification_id' => $data['classification'],
      'email' => Str::orderedUuid() . '@email.com',
      'phone' => $guardians[0]['phone'],
      'guardian_phone' => $guardians[0]['phone'],
      'photo' => $destinationUrl,
      ...collect($admissionApplication)->only(
        'first_name',
        'last_name',
        'other_names',
        'gender',
        'photo',
        'dob'
      )
    ])->create();

    foreach ($guardians as $guardian) {
      if (!User::whereEmail($guardian['email'])->exists()) {
        RecordGuardian::make([
          ...collect($guardian)->except('id', 'admission_application_id')
        ])->create($student->id);
      }
    }
    DB::commit();
  }
}
