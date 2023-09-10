<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Core\JWT;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;

class HomeExternalController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    if ($request->token) {
      return $this->useToken($request->token);
    }
    $tokenUser = $this->getTokenUserFromCookie();

    $activeEvents = $institution
      ->events()
      ->getQuery()
      ->active()
      ->with(
        'exams',
        fn($q) => $q->where('external_reference', $tokenUser->getReference())
      )
      ->get();

    $exams = $institution
      ->exams()
      ->getQuery()
      ->where('external_reference', $tokenUser->getReference())
      ->with('event')
      ->get();

    return inertia('institutions/exams/external/external-home', [
      'events' => $activeEvents,
      'exams' => $exams,
      'tokenUser' => $tokenUser
    ]);
  }

  private function useToken(string $token)
  {
    $data = JWT::decode($token, config('services.jwt.secret-key'));
    return redirect(instRoute('external.home'))->withCookie('token', $token);
    $response = new \Illuminate\Http\Response();
    return $response
      ->cookie('token', $token)
      ->redirect(instRoute('external.home'));
  }
}