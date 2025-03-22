<?php
namespace App\Actions\Admisssions;

use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\ApplicationGuardian;
use App\Models\Institution;
use Illuminate\Http\UploadedFile;
use Storage;

class ConfirmAdmissionFormPayment
{
  function __construct(private Institution $institution)
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

    if ($photo) {
      $imagePath = $photo->store(
        "{$this->institution->uuid}/admission",
        's3_public'
      );
      $publicUrl = Storage::disk('s3_public')->url($imagePath);
      $applicantData['photo'] = $publicUrl;
    }

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
  }
}
