<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Core\JWT;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TokenUser;
use Illuminate\Http\Request;

class HomeExternalController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    if ($request->token) {
      return $this->useToken($request->token, $institution);
    }
    $tokenUser = $this->getTokenUserFromCookie();

    if (!$tokenUser->name || !$tokenUser->phone) {
      return redirect(instRoute('external.token-users.edit', [$tokenUser]));
    }

    $activeEvents = $institution
      ->events()
      ->getQuery()
      ->active()
      ->with(
        'exams',
        fn($q) => $q
          ->where('examable_id', $tokenUser->id)
          ->where('examable_type', $tokenUser->getMorphClass())
      )
      ->get();

    return inertia('institutions/exams/external/external-home', [
      'events' => $activeEvents,
      'tokenUser' => $tokenUser
    ]);
  }

  private function useToken(string $token, Institution $institution)
  {
    $activeEvents = $institution
      ->events()
      ->getQuery()
      ->active()
      ->get();

    $data = JWT::decode($token, config('services.jwt.secret-key'));
    $route = instRoute('external.home');
    $eventsCount = $activeEvents->count();
    if ($eventsCount === 0) {
      $route = instRoute('external.leader-board');
    } elseif ($eventsCount === 1) {
      $route = instRoute('external.events.show', $activeEvents->first());
    }
    return redirect($route)->withCookie(TokenUser::TOKEN_COOKIE_NAME, $token);
    //   $response = new \Illuminate\Http\Response();
    //   return $response
    //     ->cookie('token', $token)
    //     ->redirect(instRoute('external.home'));
  }
}
