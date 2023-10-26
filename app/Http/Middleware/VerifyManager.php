<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

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

    if (!$user->manager_role) {
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
