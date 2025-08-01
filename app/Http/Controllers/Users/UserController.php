<?php
namespace App\Http\Controllers\Users;

use App\Core\MonnifyHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
  function index()
  {
    $user = currentUser();

    if ($user->isManager()) {
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

  function updateBvnNin(Request $request)
  {
    $request->validate([
      'type' => ['required', Rule::in(['bvn', 'nin'])],
      'value' => ['required', 'string', 'size:11']
    ]);

    $user = currentUser();
    $hasBvnNin = $user->hasBvn || $user->hasNin;
    $user->fill([$request->type => $request->value])->save();

    if (!$hasBvnNin) {
      $res = MonnifyHelper::make()->reserveAccount($user);
      if ($res->isNotSuccessful()) {
        $user->fill([$request->type => null])->save();
        return $this->message($res->message, 403);
      }
    }
    return $this->ok();
  }
}
