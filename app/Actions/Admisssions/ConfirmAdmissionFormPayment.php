<?php

namespace App\Actions\Admisssions;

use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\ApplicationGuardian;
use App\Models\Institution;
use App\Support\Media\MediaManager;
use Illuminate\Http\UploadedFile;

class ConfirmAdmissionFormPayment
{
  public function __construct(private Institution $institution)
  {
  }

  public function run(AdmissionForm $admissionForm, array $data)
  {
    $applicantData = collect($data)
      ->except('guardians')
      ->toArray();
    $guardiansData = $data['guardians'];

    /** @var UploadedFile|null $photo */
    $photo = $data['photo'] ?? null;

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
  }
}
