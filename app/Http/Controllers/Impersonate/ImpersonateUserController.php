<?php

namespace App\Http\Controllers\Impersonate;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit\SecurityActivityLogger;

class ImpersonateUserController extends Controller
{
  public function __invoke(User $user)
  {
    abort_unless(currentUser()->isAdmin(), 403);
    $impersonator = currentUser();

    app(SecurityActivityLogger::class)->impersonationStarted(
      $impersonator,
      $user,
      $user
        ->institutionUsers()
        ->with('institution')
        ->first()?->institution,
      'admin_user_list'
    );

    session([
      'impersonator_id' => $impersonator->id,
      'impersonator_type' => 'manager'
    ]);

    auth()->login($user);

    return redirect(route('user.dashboard'));
  }
}
