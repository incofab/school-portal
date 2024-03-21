<?php

namespace App\Http\Controllers\Managers\RegistrationRequests;

use App\Actions\RegisterInstitution;
use App\Actions\RegisterInstitutionGroup;
use App\Http\Controllers\Controller;
use App\Models\InstitutionGroup;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistrationRequestsController extends Controller
{
  public function index()
  {
    $user = currentUser();
    $query = $this->getQuery($user)->notRegistered();
    return Inertia::render('managers/home/list-registration-requests', [
      'registrationRequests' => paginateFromRequest(
        $query->with('partner')->latest('id')
      ),
      'institutionGroups' => InstitutionGroup::getQueryForManager($user)
        ->latest('id')
        ->get()
    ]);
  }

  public function search(Request $request)
  {
    $query = $this->getQuery(currentUser())
      ->notRegistered()
      ->search($request->search);
    return $this->ok([
      'result' => paginateFromRequest($query->with('partner')->latest('id'))
    ]);
  }

  private function getQuery(User $user)
  {
    if ($user->isAdmin()) {
      return RegistrationRequest::query();
    }
    return $user->registrationRequests()->getQuery();
  }

  public function createInstitutionGroup(
    RegistrationRequest $registrationRequest,
    Request $request
  ) {
    $user = currentUser();
    $institutionGroupData = $request->validate([
      'name' => ['required', 'string', 'unique:institution_groups,name']
    ]);

    abort_if(
      $registrationRequest->institution_group_registered_at,
      403,
      'A group has already been created for this request'
    );

    $this->canUpdate($registrationRequest);

    RegisterInstitutionGroup::run(
      $user,
      collect($registrationRequest->data)
        ->except('institution', 'reference')
        ->toArray(),
      $institutionGroupData,
      fn() => $registrationRequest
        ->fill(['institution_group_registered_at' => now()])
        ->save()
    );

    return $this->ok();
  }

  public function createInstitution(
    InstitutionGroup $institutionGroup,
    RegistrationRequest $registrationRequest
  ) {
    $this->canUpdate($registrationRequest);
    $user = currentUser();
    abort_unless(
      $user->isAdmin() || $user->id === $institutionGroup->partner_user_id,
      403,
      'Access denied'
    );

    RegisterInstitution::run(
      $institutionGroup,
      $registrationRequest->data['institution'],
      fn() => $registrationRequest
        ->fill(['institution_registered_at' => now()])
        ->save()
    );

    return $this->ok();
  }

  public function destroy(RegistrationRequest $registrationRequest)
  {
    $this->canUpdate($registrationRequest);
    $registrationRequest->delete();
    return $this->ok();
  }

  function canUpdate(RegistrationRequest $registrationRequest)
  {
    $user = currentUser();
    abort_unless(
      $user->isAdmin() || $user->id === $registrationRequest->partner_user_id,
      403,
      'Access denied'
    );
  }
}
