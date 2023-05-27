<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
  public function showLogin()
  {
    return Inertia::render('login');
  }

  public function login()
  {
    request()->validate([
      'email' => ['required', 'string', 'email'],
      'password' => ['required', 'string']
    ]);

    $credentials = request()->only(['email', 'password']);

    if (!Auth::attempt($credentials)) {
      throw ValidationException::withMessages([
        'email' => ['invalid credentials']
      ]);
    }

    return redirect()->intended(RouteServiceProvider::HOME);
  }

  public function showForgotPassword()
  {
    return Inertia::render('forgot-password');
  }

  public function forgotPassword()
  {
    request()->validate([
      'email' => ['required', 'string', 'email']
    ]);

    $status = Password::sendResetLink(request()->only('email'));

    return $status === Password::RESET_LINK_SENT
      ? back()->with(['status' => __($status)])
      : back()->withErrors(['email' => __($status)]);
  }

  public function showResetPassword(string $token)
  {
    return Inertia::render('reset-password', [
      'email' => request()->email,
      'token' => $token
    ]);
  }

  public function resetPassword()
  {
    request()->validate([
      'email' => ['required', 'string', 'email'],
      'password' => ['required', 'string', 'confirmed'],
      'token' => ['required']
    ]);

    $status = Password::reset(
      request()->only('email', 'password', 'password_confirmation', 'token'),
      function ($user, $password) {
        $user->forceFill([
          'password' => Hash::make($password)
        ]);

        // for users who had their account created by someone else
        // this way they don't see the "verify email" screen upon logging in
        if (!$user->hasVerifiedEmail()) {
          $user->markEmailAsVerified();
        }

        $user->save();

        event(new PasswordReset($user));
      }
    );

    return $status === Password::PASSWORD_RESET
      ? redirect()->route('login')
      : back()->withErrors(['email' => __($status)]);
  }

  // public function showRegister()
  // {
  //   return Inertia::render('register', []);
  // }

  // public function register()
  // {
  //   $data = request()->validate([
  //     'first_name' => ['required', 'string', 'max:255'],
  //     'last_name' => ['required', 'string', 'max:255'],
  //     'email' => ['required', 'string', 'email', 'unique:users,email'],
  //     'password' => ['required', 'string', 'confirmed', 'min:4']
  //   ]);

  //   $data['password'] = bcrypt($data['password']);

  //   $user = User::create($data);

  //   Auth::login($user);

  //   event(new Registered($user));

  //   return redirect()->intended(RouteServiceProvider::HOME);
  // }

  public function logout()
  {
    auth()->logout();
    session()->remove('impersonator_id');

    return redirect()->route('login');
  }
}
