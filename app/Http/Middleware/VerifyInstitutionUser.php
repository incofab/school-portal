<?php

namespace App\Http\Middleware;

use App\Models\Institution;
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

    if (!$user) {
      $message = 'You are not looged in.';
      return $this->eject($request, $message);
    }

    /** @var \App\Models\Institution $institution */
    $institution = Institution::query()
      ->select('institutions.*')
      ->join(
        'institution_users',
        'institution_users.institution_id',
        'institutions.id'
      )
      ->where('uuid', $request->route()->institution)
      ->where('institution_users.user_id', $user->id)
      ->with('institutionUsers')
      ->first();

    if (!$institution) {
      $message = 'You are not authorized to access this page.';
      return $this->eject($request, $message);
    }

    $request->route()->setParameter('institution', $institution);
    $request
      ->route()
      ->setParameter(
        'institutionUser',
        $institution->institutionUsers->first()
      );

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
