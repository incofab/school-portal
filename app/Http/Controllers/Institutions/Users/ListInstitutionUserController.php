<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Http\Controllers\Controller;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListInstitutionUserController extends Controller
{
  private function getQuery()
  {
    $request = request();
    return InstitutionUser::query()
      ->select('institution_users.*')
      ->where('institution_users.institution_id', currentInstitution()->id)
      ->when(
        $request->roles_in,
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
