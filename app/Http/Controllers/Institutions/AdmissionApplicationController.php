<?php

namespace App\Http\Controllers\Institutions;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdmissionApplicationRequest;
use App\Models\AdmissionApplication;
use App\Models\Institution;
use Inertia\Inertia;

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

  public function index()
  {
    $data = request()->validate(['search' => ['nullable', 'string']]);
    $query = AdmissionApplication::query()->when(
      $data['search'],
      fn($q, $value) => $q->where(
        fn($query) => $query
          ->where('first_name', 'LIKE', "%$value%")
          ->orWhere('last_name', 'LIKE', "%$value%")
          ->orWhere('other_names', 'LIKE', "%$value%")
      )
    );
    return Inertia::render('admissions/list-admission-applications', [
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
    return Inertia::render(
      'institutions/admissions/show-admission-application',
      [
        'admissionApplication' => $admissionApplication
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
