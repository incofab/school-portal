<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;

class ResetUserPasswordController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    User $user
  ) {
    $currentUser = currentUser();
    abort_if($user->id === $currentUser->id, 401);
    abort_unless($currentUser->isInstitutionAdmin(), 403);
    abort_unless($user->institutionUser(), 403);

    $user
      ->fill(['password' => Hash::make(config('app.user_default_password'))])
      ->save();
    return $this->message(
      "{$user->first_name}'s password has been reset to default (password)"
    );
  }
}
