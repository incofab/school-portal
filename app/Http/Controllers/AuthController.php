<?php

namespace App\Http\Controllers;

use App\Helpers\InstitutionBackgroundImage;
use App\Models\Institution;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
  public function showLogin()
  {
    return Inertia::render('auth/login', [
      'imageUrl' => InstitutionBackgroundImage::getBackgroundImage()
    ]);
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
    return Inertia::render('auth/forgot-password', [
      'imageUrl' => InstitutionBackgroundImage::getBackgroundImage()
    ]);
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
    return Inertia::render('auth/reset-password', [
      'email' => request()->email,
      'token' => $token,
      'imageUrl' => InstitutionBackgroundImage::getBackgroundImage()
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

  public function logout()
  {
    $currentUser = currentUser();
    $isImpersonating = session()->has('impersonator_id');
    $institutionUsers = $currentUser->institutionUsers()->get();

    auth()->logout();
    session()->remove('impersonator_id');

    if (!$isImpersonating && $institutionUsers->first()?->isStudent()) {
      return redirect()->route('student-login');
    }

    return redirect()->route('login');
  }
}
