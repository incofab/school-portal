<?php

namespace App\Http\Controllers\Impersonate;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Support\Audit\SecurityActivityLogger;

class ImpersonateInstitutionController extends Controller
{
  public function __invoke(Institution $institution)
  {
    $institution->load('institutionGroup', 'user');
    $this->authorize('impersonate', $institution);

    $user = currentUser();
    $loginUser = $institution->user;

    if (!$loginUser) {
      $loginUser = $institution
        ->institutionUsers()
        ->where('role', InstitutionUserType::Admin)
        ->with('user')
        ->first()?->user;
    }

    abort_unless($loginUser, 403, 'Admin user not found');

    app(SecurityActivityLogger::class)->impersonationStarted(
      $user,
      $loginUser,
      $institution,
      'manager_institution'
    );

    session([
      'impersonator_id' => $user->id,
      'impersonator_type' => 'manager'
    ]);
    auth()->login($loginUser);

    return redirect(route('user.dashboard'));
  }
}
