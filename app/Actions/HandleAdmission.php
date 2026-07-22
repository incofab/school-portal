<?php

namespace App\Actions;

use App\Enums\Media\MediaVisibility;
use App\Models\AdmissionApplication;
use App\Models\Institution;
use App\Models\User;
use App\Support\Media\MediaManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
    $destinationUrl = $admissionApplication->photo;

    DB::beginTransaction();
    $student = RecordStudent::make($this->institution, [
      'classification_id' => $data['classification'],
      'email' => Str::orderedUuid()->toString() . '@email.com',
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

    if ($admissionApplication->photo) {
      $path = $admissionApplication->latestMediaForCollection('admission_photo')
        ?->path;

      if ($path) {
        app(MediaManager::class)->registerExistingFile(
          path: $path,
          mediable: $student->user,
          collectionName: 'profile_photo',
          institution: $this->institution,
          uploadedBy: currentUser(),
          visibility: MediaVisibility::Public,
          meta: [
            'source_type' => $admissionApplication->getMorphClass(),
            'source_id' => $admissionApplication->id
          ],
          legacyUrlColumn: 'photo'
        );
      } else {
        $student->user->forceFill(['photo' => $destinationUrl])->save();
      }
    }

    DB::commit();
  }
}
