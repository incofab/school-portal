<?php

namespace App\Http\Controllers\Impersonate;

use App\Http\Controllers\Controller;
use App\Models\Institution;

class ImpersonateInstitutionController extends Controller
{
  public function __invoke(Institution $institution)
  {
    $institution->load('institutionGroup', 'user');
    $this->authorize('impersonate', $institution);
    $user = currentUser();

    session(['impersonator_id' => $user->id]);
    auth()->login($institution->user);

    return redirect(route('user.dashboard'));
  }
}
