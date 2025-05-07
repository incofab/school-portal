<?php

namespace App\Http\Controllers\Managers\Institutions;

use App\Actions\RegisterInstitution;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionGroup;
use Illuminate\Http\Request;

class InstitutionRegistrationController extends Controller
{
  public function create()
  {
    $user = currentUser();
    return inertia('managers/institutions/create-institution', [
      'institutionGroups' => InstitutionGroup::getQueryForManager($user)->get()
    ]);
  }

  public function store(Request $request)
  {
    $data = $request->validate(Institution::generalRule());

    $user = currentUser();
    $institutionGroup = InstitutionGroup::getQueryForManager($user)
      ->where('id', $data['institution_group_id'])
      ->with('user')
      ->firstOrFail();

    RegisterInstitution::run($institutionGroup, $data);

    /*
    DB::beginTransaction();
    $user = $institutionGroup->user;
    $institution = $user
      ->institutions()
      ->withPivotValue('role', InstitutionUserType::Admin)
      ->create([
        ...$data,
        'code' => Institution::generateInstitutionCode(),
        'uuid' => Str::orderedUuid(),
        'user_id' => $user->id
      ]);
    SeedSetupData::run($institution);
    DB::commit();
    */

    return redirect()->intended(route('managers.institutions.index'));
  }
}
