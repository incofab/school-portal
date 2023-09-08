<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Core\JWT;
use App\DTO\TokenUser;
use App\Http\Controllers\Controller;
use Cookie;

class BaseExternalController extends Controller
{
  protected TokenUser|null $tokenUser = null;

  function __construct()
  {
    $this->middleware(function ($request, $next) {
      $token = $request->token ?? Cookie::get('token');
      if (!$token) {
        return $next($token);
      }
      $data = JWT::decode($token, config('services.jwt.secret-key'));
      $this->tokenUser = new TokenUser();
      $this->tokenUser->setData((array) $data);
      return $next($request);
    });
  }
}
