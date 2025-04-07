<?php

namespace App\Http\Controllers\Institutions\Exams\External;

use App\Core\JWT;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\TokenUser;
use Illuminate\Http\Request;

class GetUserTokenController extends Controller
{
  public function __invoke(Request $request, Institution $institution)
  {
    die('<h2>Please update your app to the latest version</h2>');
    $data = $request->validate([
      'reference' => ['required', 'string', 'max:255'],
      'email' => ['nullable', 'email'],
      'phone' => ['nullable', 'string'],
      'name' => ['nullable', 'string'],
      'vendor' => ['nullable', 'string']
    ]);

    $tokenUser = TokenUser::query()->firstOrCreate(
      ['reference' => $data['reference'], 'institution_id' => $institution->id],
      [
        ...collect($data)
          ->except('vendor', 'reference')
          ->toArray(),
        'meta' => ['vendor' => $data['vendor'] ?? 'examscholars']
      ]
    );

    $token = JWT::encode(
      [TokenUser::TOKEN_USER_ID => $tokenUser->id],
      config('services.jwt.secret-key')
    );

    return $this->ok([
      'token' => $token,
      'token_url' => instRoute('external.home', ['token' => $token])
    ]);
  }
}
