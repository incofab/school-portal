<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use App\Support\Audit\SecurityActivityLogger;
use Illuminate\Http\Request;

class DeleteUserController extends Controller
{
  public function __invoke(
    Request $request,
    Institution $institution,
    User $user
  ) {
    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    $institutionUser = $user
      ->institutionUser()
      ->with('student')
      ->first();

    abort_unless($institutionUser, 403);
    $role = $institutionUser->role?->value;

    app(SecurityActivityLogger::class)->userDeleted(
      currentUser(),
      $user,
      $institution,
      $role
    );

    $user->courseTeachers()->delete();
    $user->delete();
    $institutionUser->student?->delete();
    $institutionUser->delete();

    return $this->ok();
  }
}
