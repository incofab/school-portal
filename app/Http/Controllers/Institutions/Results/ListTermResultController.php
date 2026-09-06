<?php

namespace App\Http\Controllers\Institutions\Results;

use App\Actions\CourseResult\GetGrade;
use App\Http\Controllers\Controller;
use App\Models\ClassResultInfo;
use App\Models\CourseTeacher;
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

    $gradeReport = GetGrade::getGradeReport($classResultInfo);

    return Inertia::render('institutions/results/list-term-results', [
      'termResults' => paginateFromRequest(
        $query
          ->with('academicSession', 'classification', 'student.user')
          ->oldest('term_results.position')
      ),
      'classResultInfo' => $classResultInfo,
      'gradeReport' => $gradeReport
    ]);
  }

  private function validateUser(?User $user = null)
  {
    $institutionUser = currentInstitutionUser();
    if (!$user) {
      abort_if(
        !$institutionUser->canViewAllResults() &&
          !$institutionUser->isTeacher() &&
          !$institutionUser->isStudent(),
        403
      );
      return;
    }

    $student = $user->institutionStudent();
    abort_unless($student, 403, 'This user is not a student');

      $canView = $institutionUser->canViewResultsFor($student) &&
        ($institutionUser->isAdmin() ||
        $institutionUser->isStudent() ||
        $institutionUser->isGuardian() ||
        $institutionUser->isTeacher());

    abort_unless($canView, 403, 'You cannot check this student result');
  }

  private function getQuery(?User $user = null)
  {
    $this->validateUser($user);
    $currentInstitutionUser = currentInstitutionUser();

    if (!$user) {
      if ($currentInstitutionUser->canViewAllResults()) {
        return TermResult::query();
      }

      if ($currentInstitutionUser->isTeacher()) {
        return TermResult::query()
          ->where(function ($query) use ($currentInstitutionUser) {
            $query
              ->whereHas(
                'classification',
                fn($classification) => $classification->where(
                  'form_teacher_id',
                  $currentInstitutionUser->user_id
                )
              )
              ->orWhereIn(
                'classification_id',
                CourseTeacher::query()
                  ->where('user_id', $currentInstitutionUser->user_id)
                  ->select('classification_id')
              );
          });
      }

      if ($currentInstitutionUser->isGuardian()) {
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
