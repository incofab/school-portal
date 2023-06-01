<?php

namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\TermResult;
use App\Models\User;
use App\Support\UITableFilters\TermResultUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListTermResultController extends Controller
{
  public function __invoke(Request $request, User $user = null)
  {
    $query = $this->getQuery($user);
    TermResultUITableFilters::make($request->all(), $query)->filterQuery();

    return Inertia::render('institutions/list-term-results', [
      'termResults' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification', 'student.user')
          ->oldest('term_results.position')
      )
    ]);
  }

  public function validateUser(User $user = null)
  {
    $institutionUser = currentInstitutionUser();
    if (!$user) {
      abort_if(!$institutionUser->isStaff(), 403);
      return;
    }

    if ($user->id === $institutionUser->user_id) {
      abort_unless($institutionUser->isStudent(), 403, 'You are not a student');
      return;
    }

    abort_unless(
      $institutionUser->isStaff(),
      403,
      'You cannot check another student result'
    );

    abort_unless(
      $user->isInstitutionStudent(),
      403,
      'This user is not a student'
    );
  }

  private function getQuery(User $user = null)
  {
    $this->validateUser($user);
    if ($user) {
      $student = $user->institutionStudent();
      $query = $student->termResults();
    } else {
      $query = TermResult::query();
    }
    return $query->select('term_results.*');
  }
}
