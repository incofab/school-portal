<?php

namespace App\Http\Middleware;

use App\Enums\ManagerRole;
use Closure;
use Illuminate\Http\Request;

class VerifyManager
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    $user = currentUser();

    if (!$user->hasRole([ManagerRole::Admin, ManagerRole::Partner])) {
      return $this->eject($request, 'You are not a manager');
    }

    return $next($request);
  }

  private function eject(Request $request, string $message)
  {
    return $request->expectsJson()
      ? abort(403, $message)
      : redirect()->route('user.dashboard'); //Redirect::guest(URL::route('login'));
  }
}
