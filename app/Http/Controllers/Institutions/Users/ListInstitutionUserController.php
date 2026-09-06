<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Enums\InstitutionUserType;
use App\Enums\RoleGuard;
use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Services\Institutions\InstitutionRoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListInstitutionUserController extends Controller
{
  private function getQuery()
  {
    $request = request();
    $rolesIn = $request->roles_in;
    if ($request->staffOnly) {
      $rolesIn = [
        InstitutionUserType::Admin->value,
        InstitutionUserType::Teacher->value,
        InstitutionUserType::Accountant->value
      ];
    }
    if ($request->studentsOnly) {
      $rolesIn = [
        InstitutionUserType::Student->value,
        InstitutionUserType::Alumni->value
      ];
    }

    return InstitutionUser::query()
      ->select('institution_users.*')
      // ->where('institution_users.institution_id', currentInstitution()->id)
      ->when(
        $rolesIn,
        fn($q, $value) => $q->whereIn('institution_users.type', $value)
      )
      ->when(
        $request->roles_not_in,
        fn($q, $value) => $q->whereNotIn('institution_users.type', $value)
      )
      ->when($request->role, function ($q, $value) {
        if (is_numeric($value)) {
          return $q->whereHas(
            'roles',
            fn($roleQuery) => $roleQuery
              ->whereKey((int) $value)
              ->where('roles.institution_id', currentInstitution()->id)
              ->where('roles.guard_name', RoleGuard::Web->value)
          );
        }

        return $q->where('institution_users.type', $value);
      })
      ->when(
        $request->search,
        fn($q, $value) => $q
          ->join('users', 'users.id', 'institution_users.user_id')
          ->where(
            fn($q2) => $q2
              ->where('users.last_name', 'like', "%$value%")
              ->orWhere('users.first_name', 'like', "%$value%")
              ->orWhere('users.other_names', 'like', "%$value%")
          )
      )
      ->with('user', 'student', 'roles')
      ->latest('institution_users.id');
  }

  /**
   * Display a listing of the resource.
   */
  public function index(
    Institution $institution,
    Request $request,
    InstitutionRoleService $roleService
  ) {
    return Inertia::render('institutions/users/list-institution-users', [
      'institutionUsers' => paginateFromRequest($this->getQuery()),
      'roles' => $roleService->forStaff($institution)
    ]);
  }

  public function search(Institution $institution)
  {
    return response()->json([
      'result' => paginateFromRequest($this->getQuery())
    ]);
  }
}
