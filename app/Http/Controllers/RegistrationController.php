<?php

namespace App\Http\Controllers;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Str;

class RegistrationController extends Controller
{
  public function create()
  {
    return inertia('register', []);
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      ...User::generalRule(),
      'institution' => ['required', 'array'],
      'institution.name' => ['required', 'string'],
      'institution.phone' => ['nullable', 'string'],
      'institution.email' => ['nullable', 'string'],
      'institution.address' => ['nullable', 'string']
    ]);

    $data['password'] = bcrypt($data['password']);

    DB::beginTransaction();
    /** @var \App\Models\User $user */
    $user = User::create(
      collect($data)
        ->except(['institution'])
        ->toArray()
    );

    $institution = $user
      ->institutions()
      ->withPivotValue('role', InstitutionUserType::Admin)
      ->create([
        ...$data['institution'],
        'code' => Institution::generateInstitutionCode(),
        'uuid' => Str::orderedUuid(),
        'user_id' => $user->id
      ]);
    DB::commit();

    Auth::login($user);

    event(new Registered($user));

    return redirect()->intended(
      route('institutions.dashboard', [$institution])
    );
  }
}
