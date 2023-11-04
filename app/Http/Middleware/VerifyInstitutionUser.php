<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class VerifyInstitutionUser
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

    $institution = $request->route()->institution;

    if ($user->id !== $institution['institutionUsers'][0]?->user_id) {
      $message = 'You are not authorized to access this page.';
      return $this->eject($request, $message);
    }

    View::share('institution', $institution);

    $request->merge(['institution_id' => $institution->id]);

    return $next($request);
  }

  private function eject(Request $request, string $message)
  {
    return $request->expectsJson()
      ? abort(403, $message)
      : Redirect::guest(URL::route('login'));
  }
}
