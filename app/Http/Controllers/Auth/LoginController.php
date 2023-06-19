<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Helpers\UserHelper;

class LoginController extends Controller
{
  /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

  use AuthenticatesUsers;

  /**
   * Where to redirect users after login.
   *
   * @var string
   */
  protected $redirectTo = RouteServiceProvider::HOME;

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
    $this->middleware('guest')->except('logout');
    $this->middleware('guest:admin')->except('logout');
  }

  public function username()
  {
    $login = request()->input('username');

    if (is_numeric($login) && $login[0] === '0' && strlen($login) == 11) {
      $field = 'phone';
    } elseif (filter_var($login, FILTER_VALIDATE_EMAIL)) {
      $field = 'email';
    } else {
      $field = 'username';
    }

    request()->merge([$field => $login]);

    return $field;
  }

  public function apiLogin(Request $request, UserHelper $userHelper)
  {
    $this->validateLogin($request);

    if (
      method_exists($this, 'hasTooManyLoginAttempts') &&
      $this->hasTooManyLoginAttempts($request)
    ) {
      $this->fireLockoutEvent($request);

      return $this->apiRes(
        false,
        'Too many failed attempts. You have been locked out for 2hrs'
      );
    }

    if (!$this->attemptLogin($request)) {
      $this->incrementLoginAttempts($request);

      return $this->apiRes(false, 'Credentials not match');
    } else {
      $this->clearLoginAttempts($request);
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $userIndex = $userHelper->indexPage($user);
    $userIndex['user'] = $user;
    $userIndex['token'] = $user->createLoginToken();

    return $this->apiRes(true, 'Login successful', $userIndex);
  }

  /** @deprecated */
  public function logout()
  {
    Auth::logout();

    return redirect(route('login'));
  }
}
