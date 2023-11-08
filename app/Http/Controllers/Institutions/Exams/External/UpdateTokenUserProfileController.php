<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TokenUser;
use Illuminate\Http\Request;

class UpdateTokenUserProfileController extends Controller
{
  public function edit(
    Request $request,
    Institution $institution,
    TokenUser $tokenUser
  ) {
    $t = $this->getTokenUserFromCookie();
    abort_unless($tokenUser->id === $t->id, 403, 'Not your profile');
    return inertia('institutions/exams/external/edit-token-user-profile', [
      'tokenUser' => $tokenUser
    ]);
  }

  public function update(
    Request $request,
    Institution $institution,
    TokenUser $tokenUser
  ) {
    $t = $this->getTokenUserFromCookie();
    abort_unless($tokenUser->id === $t->id, 403, 'Not your profile');

    $data = $request->validate([
      'email' => ['nullable', 'email'],
      'phone' => ['nullable', 'string'],
      'name' => ['nullable', 'string']
    ]);

    $tokenUser->fill($data)->save();

    return $this->ok();
  }
}
