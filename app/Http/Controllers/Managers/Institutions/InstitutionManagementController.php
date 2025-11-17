<?php

namespace App\Http\Controllers\Managers\Institutions;

use App\Actions\Subscriptions\GenerateInvoice;
use App\Enums\InstitutionStatus;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use App\Models\AcademicSession;
use App\Enums\TermType;
use App\Models\InstitutionGroup;

class InstitutionManagementController extends Controller
{
  public function show(Institution $institution)
  {
    return Inertia::render('managers/institutions/show-institution', [
      'institution' => $institution
    ]);
  }

  public function destroy(Request $request, Institution $institution)
  {
    $this->authorize('delete', $institution);

    abort_if(
      $institution->classifications()->count() > 0,
      403,
      'This institution contains some classes'
    );
    abort_if(
      $institution->tokenUsers()->count() > 0,
      403,
      'This institution contains some token users'
    );
    abort_if(
      $institution->courses()->count() > 0,
      403,
      'This institution contains some token subjects'
    );
    $institution->delete();
    return $this->ok();
  }

  function updateStatus(Request $request, Institution $institution)
  {
    $this->authorize('delete', $institution);
    $request->validate([
      'status' => ['required', new Enum(InstitutionStatus::class)]
    ]);
    $status = $request->status;

    if ($status === $institution->status->value) {
      return $this->ok();
    }

    $institution->fill(['status' => $status])->save();
    return $this->ok();
  }

  public function generateInvoice(
    InstitutionGroup $institutionGroup,
    AcademicSession $academicSession,
    $term
  ) {
    $termType = TermType::tryFrom($term);

    abort_unless($termType, 'Please, supply a valid term type');

    return (new GenerateInvoice(
      $institutionGroup,
      $academicSession,
      $termType
    ))->downloadAsPdf();
  }
}
