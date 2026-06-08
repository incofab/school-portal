<?php

namespace App\Http\Middleware;

use App\Enums\ManagerRole;
use App\Support\Audit\SecurityActivityLogger;
use Closure;
use Illuminate\Http\Request;

class VerifyManager
{
  /**
   * Handle an incoming request.
   *
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
    app(SecurityActivityLogger::class)->unauthorizedAccess(
      currentUser(),
      $message
    );

    return $request->expectsJson()
      ? abort(403, $message)
      : redirect()->route('user.dashboard'); // Redirect::guest(URL::route('login'));
  }
}
