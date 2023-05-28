<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ChangeUserPasswordController extends Controller
{
  function edit()
  {
    return Inertia::render('users/change-password', ['user' => currentUser()]);
  }

  function update(Request $request)
  {
    $user = currentUser();
    $data = $request->validate([
      'current_password' => [
        'required',
        'string',
        function ($attr, $value, $fail) use ($user) {
          if (!Hash::check($value, $user->password)) {
            $fail('Invalid current password');
          }
        }
      ],
      'new_password' => ['required', 'string', 'confirmed', 'min:4']
    ]);

    $user->password = Hash::make($data['new_password']);
    $user->save();

    return response()->json(['message' => 'Password changed successfully']);
  }
}
