<?php

namespace App\Http\Controllers\Impersonate;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;

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
        ->where('type', InstitutionUserType::Admin)
        ->with('user')
        ->first()?->user;
    }

    abort_unless($loginUser, 403, 'Admin user not found');

    session(['impersonator_id' => $user->id]);
    auth()->login($loginUser);

    return redirect(route('user.dashboard'));
  }
}
