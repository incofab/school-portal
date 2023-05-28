<?php
namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;

class UserController extends Controller
{
  function index()
  {
    $user = currentUser();
    // dd(['user' => $user]);
    $institutions = $user->institutions()->get();
    // dd($institutions->toArray());
    if ($institution = $institutions->first()) {
      if ($institution) {
        return redirect(route('institutions.dashboard', $institution));
      }
    }
    dd('You are not assigned to any institution yet');
    return $this->view('user.index', []);
  }
}
