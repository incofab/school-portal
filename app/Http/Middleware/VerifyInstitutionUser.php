<?php

namespace App\Http\Middleware;

use App\Enums\InstitutionStatus;
use App\Models\Institution;
use App\Support\Audit\SecurityActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class VerifyInstitutionUser
{
  /**
   * Handle an incoming request.
   *
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    $user = currentUser();

    /** @var \App\Models\Institution $institution */
    $institution = $request->route()->institution;
    if (!($institution instanceof Institution)) {
      return $this->eject(
        $request,
        'Institution not found or user is not an institution user.'
      );
    }

    $institutionUser = $institution->institutionUsers->first();

    if ($user->isManager()) {
      $message =
        'Managers must impersonate an institution admin to access institution pages.';

      return $this->eject($request, $message, $institution);
    }

    if ($institution->status !== InstitutionStatus::Active) {
      $message = 'This institution is not active. Please contact support.';

      return $this->eject($request, $message, $institution);
    }

    if ($user->id !== $institutionUser?->user_id) {
      $message = 'You are not authorized to access this page.';

      return $this->eject($request, $message, $institution);
    }

    if ($institutionUser->isSuspended()) {
      $message =
        'This account is suspended. ' .
        ($institutionUser->status_message ?? 'Please contact your admin');

      return $this->eject($request, $message, $institution);
    } elseif (!$institutionUser->isActive()) {
      $message =
        'This account is not active. ' .
        ($institutionUser->status_message ?? 'Please contact your admin');

      return $this->eject($request, $message, $institution);
    }

    View::share('institution', $institution);

    $request->merge(['institution_id' => $institution->id]);

    return $next($request);
  }

  private function eject(
    Request $request,
    string $message,
    ?Institution $institution = null
  ) {
    app(SecurityActivityLogger::class)->unauthorizedAccess(
      currentUser(),
      $message,
      $institution
    );

    return $request->expectsJson()
      ? abort(403, $message)
      : redirect(route('home.error'))->with('message', $message);
    // : Redirect::guest(URL::route('login'));
  }
}
