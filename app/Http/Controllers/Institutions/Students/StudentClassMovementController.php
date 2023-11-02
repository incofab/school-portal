<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentClassMovement;
use App\Support\UITableFilters\StudentClassMovementUITableFilters;

class StudentClassMovementController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  private function getQuery()
  {
    return StudentClassMovementUITableFilters::make(
      request()->all(),
      StudentClassMovement::query()
    )
      ->filterQuery()
      ->getQuery()
      ->with(
        'student.user',
        'destinationClass',
        'sourceClass',
        'revertReference',
        'user',
        'academicSession'
      )
      ->latest('id');
  }

  function index(Request $request)
  {
    return inertia(
      'institutions/classifications/list-student-class-movements',
      ['studentClassMovements' => paginateFromRequest($this->getQuery())]
    );
  }

  function search(Request $request)
  {
    return response()->json([
      'result' => paginateFromRequest($this->getQuery())
    ]);
  }
}
