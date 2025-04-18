<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\SessionResult;
use App\Models\TermResult;
use App\Models\User;
use App\Support\UITableFilters\SessionResultUITableFilters;
use Illuminate\Http\Request;

class SessionResultController extends Controller
{
  private function getQuery(User $currentUser)
  {
    if ($student = $currentUser->institutionStudent()) {
      return $student->sessionResults()->getQuery();
    }
    return SessionResult::query();
  }

  function index(Request $request, Institution $institution)
  {
    $currentUser = currentUser();
    $query = $this->getQuery($currentUser);
    SessionResultUITableFilters::make($request->all(), $query)->filterQuery();
    $query->with('student.user', 'classification', 'academicSession');

    return inertia('institutions/list-session-results', [
      'sessionResults' => paginateFromRequest($query)
    ]);
  }

  function show(Institution $institution, SessionResult $sessionResult)
  {
    $binding = [
      'academic_session_id' => $sessionResult->academic_session_id,
      'classification_id' => $sessionResult->classification_id,
      'student_id' => $sessionResult->student_id,
      'for_mid_term' => false
    ];

    $termResultDetails = [];
    foreach (TermType::cases() as $key => $term) {
      $binding['term'] = $term;
      $termResultDetails[$term->value] = [
        'termResult' => TermResult::query()
          ->where($binding)
          ->first(),
        'courseResults' => CourseResult::query()
          ->where($binding)
          ->with('course')
          ->get()
          ->keyBy('course_id'),
        'courseResultInfo' => CourseResultInfo::query()
          ->where(
            collect($binding)
              ->except('student_id')
              ->toArray()
          )
          ->get()
          ->keyBy('course_id')
      ];
    }

    return inertia(
      'institutions/session-result-sheets/session-result-template-1',
      [
        'sessionResult' => $sessionResult->load(
          'student.user',
          'academicSession',
          'student',
          'classification'
        ),
        'termResultDetails' => $termResultDetails
      ]
    );
  }

  function destroy(Institution $institution, SessionResult $sessionResult)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    $sessionResult->delete();

    return $this->ok();
  }
}
