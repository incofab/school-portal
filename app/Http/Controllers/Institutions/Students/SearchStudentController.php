<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\UITableFilters\StudentUITableFilters;
use Illuminate\Http\Request;

class SearchStudentController extends Controller
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

    $query = Student::query();
    StudentUITableFilters::make($request->all(), $query)->filterQuery();

    return response()->json([
      'result' => paginateFromRequest(
        $query->with('user', 'classification')->latest('students.id')
      )
    ]);
  }
}
