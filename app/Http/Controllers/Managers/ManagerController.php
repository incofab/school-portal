<?php
namespace App\Http\Controllers\Managers;

use App\Enums\ManagerRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ManagerController extends Controller
{
  function dashboard(Request $request)
  {
    return inertia('managers/dashboard');
  }

  function index(Request $request)
  {
    $query = User::query()
      ->whereHas('roles')
      ->with('roles')
      ->withCount('partnerInstitutionGroups');
    return inertia('managers/home/list-managers', [
      'managers' => paginateFromRequest($query)
    ]);
  }

  function create()
  {
    return inertia('managers/home/create-manager');
  }

  function store(Request $request)
  {
    $data = $request->validate([
      ...User::generalRule(),
      'username' => ['required', 'unique:users,username'],
      'role' => [
        'required',
        new Enum(ManagerRole::class),
        function ($attr, $value, $fail) {
          if ($value === ManagerRole::Admin->value) {
            $fail('Admin role cannot be added through this form');
          }
        }
      ]
    ]);
    $user = User::query()->create(
      collect($data)
        ->except('role')
        ->toArray()
    );
    $user->assignRole($data['role']);
    return $this->ok();
  }

  function destroy(User $user)
  {
    abort_if(
      $user->partnerInstitutionGroups()->count() > 0,
      403,
      'This manager cannot be deleted because it is attached to an institution'
    );
    $user->delete();
    return $this->ok();
  }
}
