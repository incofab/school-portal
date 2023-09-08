<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Core\JWT;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;

class GetUserTokenController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    $data = $request->validate([
      'reference' => ['required', 'string', 'max:255'],
      'email' => ['nullable', 'email'],
      'phone' => ['nullable', 'string'],
      'name' => ['nullable', 'string']
    ]);
    $token = JWT::encode($data, config('services.jwt.secret-key'));
    // return response()->json(['token' => $token]);
    return $this->ok(['token' => $token]);
  }
}
