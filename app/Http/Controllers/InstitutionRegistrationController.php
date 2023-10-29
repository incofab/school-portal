<?php

namespace App\Http\Controllers;

use App\Actions\SeedInitialAssessment;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Str;

class InstitutionRegistrationController extends Controller
{
  public function create()
  {
    return inertia('register', []);
  }

  public function store(Request $request)
  {
    abort_unless(
      strtolower($request->key) === 'master',
      403,
      'Access denied, contact admin'
    );
    $data = $request->validate([
      ...User::generalRule(),
      'institution' => ['required', 'array'],
      ...Institution::generalRule('institution.')
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
    SeedInitialAssessment::run($institution);
    DB::commit();

    Auth::login($user);

    event(new Registered($user));

    return redirect()->intended(
      route('institutions.dashboard', [$institution->uuid])
    );
  }
}
