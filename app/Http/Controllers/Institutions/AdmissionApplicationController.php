<?php

namespace App\Http\Controllers\Institutions;

use Inertia\Inertia;
use App\Models\Institution;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Actions\RecordStudent;
use App\Enums\InstitutionUserType;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\AdmissionApplicationRequest;
use App\Mail\AdmissionLetterMail;
use App\Models\Classification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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

  // public function index()
  // {
  //   $data = request()->validate(['search' => ['nullable', 'string']]);
  //   $query = AdmissionApplication::query()->when(
  //     $data['search'],
  //     fn($q, $value) => $q->where(
  //       fn($query) => $query
  //         ->where('first_name', 'LIKE', "%$value%")
  //         ->orWhere('last_name', 'LIKE', "%$value%")
  //         ->orWhere('other_names', 'LIKE', "%$value%")
  //     )
  //   );
  //   return Inertia::render('admissions/list-admission-applications', [
  //     'admissionApplications' => paginateFromRequest($query)
  //   ]);
  // }

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
    $data = $request->validated();
    info($institution->uuid);
    if ($request->photo) {
      $imagePath = $request->photo->store(
        "{$institution->uuid}/admission",
        's3_public'
      );
      $publicUrl = Storage::disk('s3_public')->url($imagePath);
      $data['photo'] = $publicUrl;
    }

    $admissionApplication = $institution
      ->admissionApplications()
      ->create($data);

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
    $data = request()->validate([
      'admission_status' => ['required', 'string'],
      'classification' => ['nullable']
    ]);

    // $admissionApplication
    //   ->fill([
    //     'admission_status' => $data['admission_status']
    //   ])
    //   ->save();

    if ($data['admission_status'] === 'admitted') {
      $sourcePath = $admissionApplication->photo;

      $parts = explode('/', $sourcePath);
      $fileName = end($parts);
      $destinationPath = 'avatars/users/' . $fileName;
      $destinationUrl = $parts[0] . '//' . $parts[2] . '/' . env('AWS_BUCKET') . '/avatars/users/' . $fileName;

      // Use the Storage facade to put the image in the S3 bucket
      // Storage::disk('s3_public')->put(
      //   $destinationPath,
      //   file_get_contents($sourcePath)
      // );

      // RecordStudent::make([
      //   // ...$admission->only('first_name', 'last_name', 'other_names', 'gender'),
      //   'first_name' => $admissionApplication["first_name"],
      //   'last_name' => $admissionApplication["last_name"],
      //   'other_names' => $admissionApplication["other_names"],
      //   'gender' => $admissionApplication["gender"],
      //   'phone' => $admissionApplication["fathers_phone"],
      //   'photo' => $destinationUrl,
      //   'email' => Str::orderedUuid() . '@email.com',
      //   'password' => 'password', //Default
      //   'classification_id' => $data['classification']
      // ])->create();

      $dUrl = route('institutions.admissions.letter', [
        'institution' => $institution->uuid,
        'student' => 32, //Should not be a static value
      ]);

      Mail::to($admissionApplication->fathers_email)->queue(new AdmissionLetterMail(User::first(), $dUrl));
    }

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
    AdmissionApplication $admission
  ) {
    // dd($admission["first_name"]);

    return Inertia::render(
      'institutions/admissions/show-admission-application',
      [
        'admissionApplication' => $admission
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
