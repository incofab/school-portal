<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\SecurityActivityLogger;
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

    $newPassword = config('app.user_default_password', 'password');
    ModelAudit::withoutAuditingFor(User::class, function () use (
      $user,
      $newPassword
    ) {
      $user->fill(['password' => Hash::make($newPassword)])->save();
    });

    app(SecurityActivityLogger::class)->passwordResetByAdmin(
      $currentUser,
      $user,
      $institution
    );

    return $this->message(
      "{$user->first_name}'s password has been reset to default: ({$newPassword})"
    );
  }
}
