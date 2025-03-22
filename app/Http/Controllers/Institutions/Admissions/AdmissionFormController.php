<?php

namespace App\Http\Controllers\Institutions\Admissions;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdmissionFormController extends Controller
{
  function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  // Admin/Institution side
  public function index(Institution $institution)
  {
    $query = $institution->admissionForms();
    return Inertia::render('institutions/admissions/list-admission-forms', [
      'admissionForms' => paginateFromRequest($query)
    ]);
  }

  public function search(Institution $institution)
  {
    return response()->json([
      'result' => AdmissionForm::query()
        ->when(
          request('search'),
          fn($q, $search) => $q->where('title', 'like', "%$search%")
        )
        ->latest('title')
        ->get()
    ]);
  }

  public function create(Institution $institution)
  {
    return Inertia::render(
      'institutions/admissions/create-edit-admission-form'
    );
  }

  public function store(Request $request, Institution $institution)
  {
    $data = $request->validate(AdmissionForm::createRule());

    $admissionForm = $institution->admissionForms()->create($data);

    return $this->ok(['admissionForm' => $admissionForm]);
  }

  public function edit(Institution $institution, AdmissionForm $admissionForm)
  {
    return Inertia::render(
      'institutions/admissions/create-edit-admission-form',
      ['admissionForm' => $admissionForm]
    );
  }

  public function update(
    Request $request,
    Institution $institution,
    AdmissionForm $admissionForm
  ) {
    $data = $request->validate(AdmissionForm::createRule());

    $admissionForm->update($data);

    return $this->ok(['admissionForm' => $admissionForm]);
  }

  public function destroy(
    Institution $institution,
    AdmissionForm $admissionForm
  ) {
    if (
      AdmissionApplication::where(
        'admission_form_id',
        $admissionForm->id
      )->exists()
    ) {
      $admissionForm->delete();
    } else {
      $admissionForm->forceDelete();
    }
    return $this->ok();
  }

  // public function show(Institution $institution, AdmissionForm $admissionForm)
  // {
  //   return Inertia::render('public/admission-forms/show', [
  //     'admissionForm' => $admissionForm
  //   ]);
  // }
}
