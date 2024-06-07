<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\InstitutionUser;
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
      ->where('institution_users.institution_id', currentInstitution()->id)
      ->when(
        $rolesIn,
        fn($q, $value) => $q->whereIn('institution_users.role', $value)
      )
      ->when(
        $request->roles_not_in,
        fn($q, $value) => $q->whereNotIn('institution_users.role', $value)
      )
      ->when(
        $request->role,
        fn($q, $value) => $q->where('institution_users.role', $value)
      )
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
      ->with('user', 'student')
      ->latest('institution_users.id');
  }

  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    return Inertia::render('institutions/users/list-institution-users', [
      'institutionUsers' => paginateFromRequest($this->getQuery())
    ]);
  }

  public function search()
  {
    return response()->json([
      'result' => paginateFromRequest($this->getQuery())
    ]);
  }
}
