<?php

namespace App\Http\Middleware;

use App\Enums\InstitutionStatus;
use App\Enums\InstitutionUserStatus;
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

    /** @var \App\Models\Institution $institution */
    $institution = $request->route()->institution;
    $institutionUser = $institution->institutionUsers->first();

    if ($institution->status !== InstitutionStatus::Active) {
      $message = 'This institution is not active. Please contact support.';
      return $this->eject($request, $message);
    }

    if ($user->id !== $institutionUser?->user_id) {
      $message = 'You are not authorized to access this page.';
      return $this->eject($request, $message);
    }

    if ($institutionUser->status !== InstitutionUserStatus::Active) {
      $message = 'This account is suspended. Please contact your admin';
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
