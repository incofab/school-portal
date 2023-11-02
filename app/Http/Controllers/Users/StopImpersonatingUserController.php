<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StopImpersonatingUserController extends Controller
{
  public function __invoke(Request $request)
  {
    abort_unless(session()->has('impersonator_id'), 403);

    auth()->login(User::find(session('impersonator_id')));

    session(['impersonator_id' => null]);

    return redirect(route('managers.institutions.index'));
  }
}
