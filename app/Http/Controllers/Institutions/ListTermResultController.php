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
    $query = $this->getQuery($user)->select('term_results.*');
    TermResultUITableFilters::make($request->all(), $query)
      ->joinStudent()
      ->filterQuery()
      ->getQuery()
      ->oldest('users.last_name');

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
    if (!$user) {
      if (currentInstitutionUser()->isStaff()) {
        return TermResult::query();
      }
      $user = currentUser();
    }

    $student = $user->institutionStudent();
    return $student->termResults();
  }
}
