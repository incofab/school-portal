<?php
namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\InstitutionUser;

class UserController extends Controller
{
  function index()
  {
    $user = currentUser();

    $institutionUser = InstitutionUser::whereUser_id($user->id)->first();

    if ($institutionUser) {
      return redirect(
        route('institution.dashboard', $institutionUser->institution->id)
      );
    }

    return $this->view('user.index', []);
  }
}
