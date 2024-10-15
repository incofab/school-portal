<?php

namespace App\Http\Controllers\Institutions;

use App\Actions\HandleAdmission;
use App\Models\User;
use Inertia\Inertia;
use App\Models\Student;
use App\Models\Institution;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Actions\RecordStudent;
use App\Models\Classification;
use App\Actions\RecordGuardian;
use App\Mail\AdmissionLetterMail;
use App\Enums\InstitutionUserType;
use Illuminate\Support\Facades\DB;
use App\Models\ApplicationGuardian;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\AdmissionApplicationRequest;

class AdmissionApplicationController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except([
      'create',
      'successMessage',
      'store',
      'admissionLetter'
    ]);
  }

  function index()
  {
    $query = AdmissionApplication::query();
    return Inertia::render(
      'institutions/admissions/list-admission-applications',
      [
        'admissionApplications' => paginateFromRequest($query)
      ]
    );
  }

  public function create(Institution $institution)
  {
    return Inertia::render('institutions/admissions/admission-application', [
      'institution' => $institution
    ]);
  }

  public function store(
    Institution $institution,
    AdmissionApplicationRequest $request
  ) {
    //info($institution->uuid);

    $data = $request->validated();
    // info($data);
    // dd();

    $applicantData = collect($data)->except('guardians')->toArray();
    $guardiansData = $data['guardians'];

    if ($request->photo) {
      $imagePath = $request->photo->store(
        "{$institution->uuid}/admission",
        's3_public'
      );
      $publicUrl = Storage::disk('s3_public')->url($imagePath);
      $applicantData['photo'] = $publicUrl;
    }

    // Create the Admission Application
    $admissionApplication = $institution
      ->admissionApplications()
      ->create($applicantData);

    // Loop through each guardian data and create the records
    foreach ($guardiansData as $guardianData) {
      $modGuardianData = [...$guardianData, 'admission_application_id' => $admissionApplication->id];
      ApplicationGuardian::create($modGuardianData);
    }

    return $this->ok(['data' => $admissionApplication]);
  }

  function edit(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    return inertia('institutions/admissions/admission-application', [
      'admissionApplication' => $admissionApplication
    ]);
  }

  public function updateStatus(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    $admissionApplication->load('applicationGuardians');
    $loaded = collect($admissionApplication)->toArray();
    $guardians = $loaded['application_guardians'];

    $data = request()->validate([
      'admission_status' => ['required', 'string'],
      'classification' => ['nullable']
    ]);

    //== If Admitted, fill the necessary DB Tables with the needed information
    if ($data['admission_status'] === 'admitted') { //Handle_Admission
      HandleAdmission::make()->admitStudent($admissionApplication, $guardians, $data);
    }

    //== Update the 'admission_status' on the 'admission_applications' DB Table
    $admissionApplication
      ->fill(['admission_status' => $data['admission_status']])
      ->save();

    return $this->ok();
  }

  public function successMessage(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    return Inertia::render(
      'institutions/admissions/admission-application-success',
      [
        'institution' => $institution,
        'admissionApplication' => $admissionApplication
      ]
    );
  }

  public function show(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {

    $admissionApplication->load('applicationGuardians');


    return Inertia::render(
      'institutions/admissions/show-admission-application',
      [
        'admissionApplication' => $admissionApplication,
        // 'applicationGuardians' => $admissionApplication->applicationGuardians
      ]
    );
  }

  public function admissionLetter(
    Institution $institution,
    Student $student
  ) {
    // $data = $student->load('user.institutionUser', 'classification');
    // dd($data);

    return Inertia::render('institutions/admissions/show-admission-letter', [
      'student' => $student->load('user.institutionUser', 'classification'),
    ]);
  }

  public function destroy(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    $admissionApplication->delete();
    return $this->ok();
  }
}
