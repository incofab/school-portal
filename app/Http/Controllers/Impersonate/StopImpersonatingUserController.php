<?php

namespace App\Http\Controllers\Impersonate;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StopImpersonatingUserController extends Controller
{
  public function __invoke(Request $request)
  {
    abort_unless(session()->has('impersonator_id'), 403);

    $impersonatorId = session('impersonator_id');
    $impersonatorType = session('impersonator_type');
    $impersonatorInstitutionId = session('impersonator_institution_id');

    auth()->login(User::find($impersonatorId));

    session([
      'impersonator_id' => null,
      'impersonator_type' => null,
      'impersonator_institution_id' => null
    ]);

    if ($impersonatorType === 'guardian' && $impersonatorInstitutionId) {
      return redirect(route('institutions.dashboard', $impersonatorInstitutionId));
    }

    return redirect(route('managers.institutions.index'));
  }
}
