<?php

namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdmissionApplicationRequest;
use App\Models\AdmissionApplication;
use App\Models\Institution;
use Inertia\Inertia;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class AdmissionApplicationController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except([
      'create',
      'successMessage',
      'store'
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
    return Inertia::render('institutions/admissions/list-admission-applications', [
      'admissionApplications' => paginateFromRequest($query)
    ]);
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
    $admissionApplication = $institution
      ->admissionApplications()
      ->create($request->validated());

    return $this->ok(['data' => $admissionApplication]);
  }

  function edit(Institution $institution, AdmissionApplication $admissionApplication)
  {
    return inertia('institutions/admissions/admission-application', [
      'admissionApplication' => $admissionApplication
    ]);
  }

  public function updateStatus(Institution $institution, AdmissionApplication $admissionApplication)
  {
    $data = request()->validate([
      'admission_status' => ['required', 'string']
    ]);

    $admissionApplication->fill($data)->save();
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
    AdmissionApplication $admission,
  ) {
    return Inertia::render(
      'institutions/admissions/show-admission-application',
      [
        'admissionApplication' => $admission
      ]
    );
  }

  public function destroy(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    $admissionApplication->delete();
    return $this->ok();
  }
}
