<?php

namespace App\Actions\Admisssions;

use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\ApplicationGuardian;
use App\Models\Institution;
use App\Support\Media\MediaManager;
use DB;
use Illuminate\Http\UploadedFile;

class RecordAdmissionApplication
{
  public static $sheetColumnMapping = [
    'A' => 'first_name',
    'B' => 'last_name',
    'C' => 'other_names',
    'D' => 'gender',
    'E' => 'guardian_no',
    'F' => 'intended_class_of_admission'
  ];

  public function __construct(private Institution $institution)
  {
  }

  public function run(AdmissionForm $admissionForm, array $data)
  {
    $applicantData = collect($data)
      ->except('guardians')
      ->toArray();
    $guardiansData = $data['guardians'] ?? [];

    /** @var ?UploadedFile $photo */
    $photo = $data['photo'] ?? null;

    DB::beginTransaction();
    // Create the Admission Application
    $admissionApplication = $this->institution
      ->admissionApplications()
      ->create([
        ...$applicantData,
        'application_no' => AdmissionApplication::generateApplicationNo(),
        'admission_form_id' => $admissionForm->id
      ]);

    // Loop through each guardian data and create the records
    foreach ($guardiansData as $guardianData) {
      $modGuardianData = [
        ...$guardianData,
        'admission_application_id' => $admissionApplication->id
      ];
      ApplicationGuardian::create($modGuardianData);
    }

    if ($photo) {
      app(MediaManager::class)->storeUploadedFile(
        $photo,
        $admissionApplication,
        'admission_photo',
        "{$this->institution->uuid}/admission",
        $this->institution,
        currentUser(),
        legacyUrlColumn: 'photo'
      );
    }

    DB::commit();

    return $admissionApplication;
  }
}
