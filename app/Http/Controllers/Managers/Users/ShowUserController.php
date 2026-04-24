<?php

namespace App\Http\Controllers\Managers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ShowUserController extends Controller
{
  public function show(User $user)
  {
    $user->load([
      'roles',
      'institutionUsers.institution',
      'institutionUsers.student.classification'
    ]);

    return Inertia::render('managers/users/show', [
      'userModel' => $user
    ]);
  }

  public function resetPassword(User $user)
  {
    $currentUser = currentUser();
    abort_if(
      $user->id === $currentUser->id,
      403,
      'You cannot reset your own password here.'
    );

    $newPassword = Hash::make(config('app.user_default_password', 'password'));
    $user->fill(['password' => $newPassword])->save();

    return $this->message(
      "{$user->full_name}'s password has been reset to default: ({$newPassword})."
    );
  }
}
