<?php

namespace App\Http\Controllers\Institutions\Users;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\UITableFilters\UserUITableFilters;
use Illuminate\Http\Request;

class SearchUserController extends Controller
{
  public function __invoke(Request $request)
  {
    $institutionUser = currentInstitutionUser();
    abort_if(
      in_array($institutionUser->role, [
        InstitutionUserType::Alumni,
        InstitutionUserType::Student
      ]),
      403
    );

    $query = User::query()->select('users.*');
    UserUITableFilters::make($request->all(), $query)->filterQuery();

    return response()->json([
      'result' => paginateFromRequest($query)
    ]);
  }
}
