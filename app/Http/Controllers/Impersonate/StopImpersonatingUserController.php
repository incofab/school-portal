<?php

namespace App\Http\Controllers\Impersonate;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit\SecurityActivityLogger;
use Illuminate\Http\Request;

class StopImpersonatingUserController extends Controller
{
  public function __invoke(Request $request)
  {
    abort_unless(session()->has('impersonator_id'), 403);

    $impersonatorId = session('impersonator_id');
    $impersonatorType = session('impersonator_type');
    $impersonatorInstitutionId = session('impersonator_institution_id');
    $target = currentUser();
    $impersonator = User::findOrFail($impersonatorId);
    $institution = $impersonatorInstitutionId
      ? \App\Models\Institution::find($impersonatorInstitutionId)
      : $target
        ->institutionUsers()
        ->with('institution')
        ->first()?->institution;

    app(SecurityActivityLogger::class)->impersonationStopped(
      $impersonator,
      $target,
      $institution,
      $impersonatorType
    );

    auth()->login($impersonator);

    session([
      'impersonator_id' => null,
      'impersonator_type' => null,
      'impersonator_institution_id' => null
    ]);

    if ($impersonatorType === 'guardian' && $impersonatorInstitutionId) {
      return redirect(
        route('institutions.dashboard', $impersonatorInstitutionId)
      );
    }

    return redirect(route('managers.institutions.index'));
  }
}
