<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use App\Core\ErrorCodes;
use App\Enums\InstitutionUserType;
use App\Models\TokenUser;
use App\Support\Res;
use Illuminate\Routing\ControllerMiddlewareOptions;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  protected $page = 1;
  protected $lastIndex = 0;
  protected $numPerPage = 100;

  public function view($view, $data = [], $merge = [])
  {
    if (!isset($data['page'])) {
      $data['page'] = $this->page;
    }

    if (!isset($data['numPerPage'])) {
      $data['numPerPage'] = $this->numPerPage;
    }

    return view($view, $data, $merge);
  }

  function redirect($redirect, $ret)
  {
    return $redirect
      ->with($ret['success'] ? 'error' : 'success', $ret['message'])
      ->withInput()
      ->withErrors(Arr::get($ret, 'val'));
  }

  function apiRes(
    bool $success,
    string $message,
    $data = [],
    $errorCode = ErrorCodes::OK,
    $httpStatusCode = 200
  ) {
    $arr = [
      'success' => $success,
      'message' => $message,
      'data' => $data
    ];

    return response()->json($arr, $httpStatusCode);
  }

  function emitResponseRet(array $ret)
  {
    $ret['error_code'] = $ret['success'] ? ErrorCodes::OK : ErrorCodes::FAILED;

    return response()->json($ret);
  }

  function apiEmitResponse($data)
  {
    return response()->json($data);
  }

  protected function onlyAdmins()
  {
    $this->middleware(function ($request, $next) {
      abort_unless(
        currentInstitutionUser()->role === InstitutionUserType::Admin,
        403,
        'You are not allowed to access this'
      );
      return $next($request);
    });
  }

  protected function allowedRoles(array $roles): ControllerMiddlewareOptions
  {
    return $this->middleware(function ($request, $next) use ($roles) {
      abort_unless(
        in_array(currentInstitutionUser()->role, $roles),
        403,
        'This role is not part of the allowed roles for this operation'
      );
      return $next($request);
    });
  }

  protected function getTokenUserFromCookie(): TokenUser
  {
    $token = \Cookie::get(TokenUser::TOKEN_COOKIE_NAME);
    abort_unless($token, 403, 'Token not found');
    $data = \App\Core\JWT::decode($token, config('services.jwt.secret-key'));
    $tokenUser = TokenUser::query()->findOrFail(
      $data->{TokenUser::TOKEN_USER_ID}
    );
    return $tokenUser;
  }

  protected function ok($data = [])
  {
    return response()->json(['ok' => true, ...$data]);
  }
  protected function message(string $message)
  {
    return response()->json(['message' => $message]);
  }

  protected function res(
    Res $res,
    string $successRoute = null,
    $failureRoute = null
  ) {
    if ($res->success && $successRoute) {
      $obj = $successRoute ? redirect($successRoute) : redirect()->back();
      return $obj->with('message', $res->message);
    }

    $obj = $failureRoute ? redirect($failureRoute) : redirect()->back();

    return $obj->with('error', $res->message)->withInput();
  }
}
