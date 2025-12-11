<?php

namespace App\Http\Controllers\Institutions\Results;

use App\Http\Controllers\Controller;
use App\Models\ClassResultInfo;
use App\Models\GuardianStudent;
use App\Models\Institution;
use App\Models\TermResult;
use App\Models\User;
use App\Support\UITableFilters\TermResultUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ListTermResultController extends Controller
{
  public function index(
    Request $request,
    Institution $institution,
    ?User $user = null
  ) {
    $query = $this->getQuery($user)->select('term_results.*');
    $finalQuery = TermResultUITableFilters::make($request->all(), $query)
      ->joinStudent()
      ->dontUseCurrentTerm()
      ->filterQuery()
      ->getQuery();
    return $this->displayIndex($finalQuery);
  }

  public function indexByClassResultInfo(
    Request $request,
    Institution $institution,
    ClassResultInfo $classResultInfo
  ) {
    $query = $classResultInfo->termResultsQuery(
      fn($filter) => $filter->joinStudent()
    );
    return $this->displayIndex($query, $classResultInfo);
  }

  private function displayIndex(
    \Illuminate\Database\Eloquent\Builder $query,
    ?ClassResultInfo $classResultInfo = null
  ) {
    $query
      ->oldest('users.last_name')
      ->latest('term_results.academic_session_id');

    return Inertia::render('institutions/results/list-term-results', [
      'termResults' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification', 'student.user')
          ->oldest('term_results.position')
      ),
      'classResultInfo' => $classResultInfo
    ]);
  }

  private function validateUser(?User $user = null)
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

    if ($institutionUser->isGuardian()) {
      abort_unless(
        GuardianStudent::isGuardianOfStudent(
          $institutionUser->user_id,
          $user->student->id
        ),
        403,
        'You are not a guardian to this student'
      );
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

  private function getQuery(?User $user = null)
  {
    $this->validateUser($user);
    $currentInstitutionUser = currentInstitutionUser();

    if (!$user) {
      if ($currentInstitutionUser->isStaff()) {
        return TermResult::query();
      } elseif ($currentInstitutionUser->isGuardian()) {
        abort(403, 'Select a  student first');
      }
      return $this->getStudentResultQuery(currentUser());
    }

    return $this->getStudentResultQuery($user);
  }

  private function getStudentResultQuery(User $user)
  {
    return $user
      ->institutionStudent()
      ?->termResults()
      ->getQuery();
  }
}
