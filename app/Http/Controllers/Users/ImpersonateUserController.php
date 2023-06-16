<?php

namespace App\Http\Controllers\Users;

use App\Enums\ManagerRole;
use App\Http\Controllers\Controller;
use App\Models\User;

class ImpersonateUserController extends Controller
{
  public function __invoke(User $user)
  {
    abort_unless(currentUser()->manager_role === ManagerRole::Admin, 403);

    session(['impersonator_id' => currentUser()->id]);

    auth()->login($user);

    return redirect(route('home'));
  }
}
