<?php

namespace App\Http\Controllers\Managers\InstitutionGroups;

use App\Actions\RegisterInstitutionGroup;
use App\Http\Controllers\Controller;
use App\Models\InstitutionGroup;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionGroupsController extends Controller
{
  public function index(Request $request)
  {
    $user = currentUser();
    return Inertia::render(
      'managers/institution-groups/list-institution-groups',
      [
        'institutionGroups' => paginateFromRequest(
          InstitutionGroup::getQueryForManager($user)
            ->withCount('institutions')
            ->latest('id')
        )
      ]
    );
  }

  function search(Request $request)
  {
    $query = InstitutionGroup::getQueryForManager(currentUser())->when(
      $request->search,
      fn($q, $value) => $q->where('name', 'LIKE', "%$value%")
    );
    return response()->json([
      'result' => paginateFromRequest($query->latest('id'))
    ]);
  }

  function create()
  {
    return inertia('managers/institution-groups/create-institution-group');
  }

  function store(Request $request)
  {
    $data = $request->validate([
      ...User::generalRule(),
      'institution_group' => ['required', 'array'],
      'institution_group.name' => ['required', 'string', 'max:255']
    ]);

    $userData = [
      ...collect($data)
        ->except('institution_group')
        ->toArray(),
      'password' => bcrypt($data['password'])
    ];
    $institutionGroupData = $data['institution_group'];

    RegisterInstitutionGroup::run(
      currentUser(),
      $userData,
      $institutionGroupData
    );

    // DB::beginTransaction();
    // $user = User::query()->create($userData);
    // currentUser()
    //   ->partnerInstitutionGroups()
    //   ->create([...$institutionGroupData, 'user_id' => $user->id]);
    // DB::commit();

    return $this->ok();
  }

  function edit(InstitutionGroup $institutionGroup)
  {
    $this->authorize('update', $institutionGroup);
    return inertia('managers/institution-groups/edit-institution-group', [
      'institutionGroup' => $institutionGroup
    ]);
  }

  function update(Request $request, InstitutionGroup $institutionGroup)
  {
    $this->authorize('update', $institutionGroup);
    $data = $request->validate([
      'name' => ['required', 'string', 'max:255']
    ]);
    $institutionGroup->update($data);
    return $this->ok();
  }

  public function destroy(Request $request, InstitutionGroup $institutionGroup)
  {
    $this->authorize('delete', $institutionGroup);
    abort_if(
      $institutionGroup->institutions()->count() > 0,
      403,
      'This group contains some institution'
    );
    $institutionGroup->delete();
    return $this->ok();
  }
}
