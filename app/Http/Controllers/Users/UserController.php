<?php
namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
  function index()
  {
    $user = currentUser();

    if ($user->manager_role) {
      return redirect(route('managers.dashboard'));
    }

    $institutions = $user->institutions()->get();
    if ($institutions->isEmpty()) {
      dd('You are not assigned to any institution yet');
    }
    // dd($institutions?->toArray());
    if ($institutions->count() === 1) {
      return redirect(route('institutions.dashboard', $institutions->first()));
    }

    return inertia('users/select-institution', [
      'institutions' => $institutions
    ]);
  }
}
