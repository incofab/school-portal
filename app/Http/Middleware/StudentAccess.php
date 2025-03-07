<?php

namespace App\Http\Middleware;

use App\Enums\InstitutionUserType;
use Closure;
use Illuminate\Http\Request;

class StudentAccess
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
    $student = $request->route('student');

    if ($student->user_id === $user->id) {
      return $next($request);
    }

    if (
      $user->hasInstitutionRole([
        InstitutionUserType::Admin,
        InstitutionUserType::Teacher,
        InstitutionUserType::Accountant
      ])
    ) {
      return $next($request);
    }

    if (
      $user
        ->guardianStudents()
        ->getQuery()
        ->where('student_id', $student->id)
        ->exists()
    ) {
      return $next($request);
    }

    return $this->eject($request, 'You are not allowed to access this page');
  }

  private function eject(Request $request, string $message)
  {
    return $request->expectsJson()
      ? abort(403, $message)
      : redirect()->route('user.dashboard'); //Redirect::guest(URL::route('login'));
  }
}
