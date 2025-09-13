<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Actions\CourseResult\GenerateCourseSessionResult;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Models\SessionResult;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use App\Support\SettingsHandler;
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

  function index(Institution $institution, Request $request)
  {
    $currentUser = currentUser();
    $institutionUser = currentInstitutionUser();
    $student = $institutionUser
      ?->student()
      ->with('user')
      ->first();
    abort_unless(
      $institutionUser->isAdmin() || $student,
      403,
      'You are not a student of this institution'
    );
    $query = $this->getQuery($currentUser);
    SessionResultUITableFilters::make($request->all(), $query)->filterQuery();
    $query->with('student.user', 'classification', 'academicSession');

    return inertia('institutions/list-session-results', [
      'sessionResults' => paginateFromRequest($query),
      'student' => $student,
      'classifications' => Classification::all(),
      'academicSessions' => AcademicSession::all()
    ]);
  }

  function indexByStudent(
    Institution $institution,
    Student $student,
    Request $request
  ) {
    $this->authorize('view', $student);

    $query = $student->sessionResults()->getQuery();
    SessionResultUITableFilters::make($request->all(), $query)->filterQuery();
    $query->with('student.user', 'classification', 'academicSession');

    return inertia('institutions/list-session-results', [
      'sessionResults' => paginateFromRequest($query),
      'student' => $student->load('user'),
      'classifications' => Classification::all(),
      'academicSessions' => AcademicSession::all()
    ]);
  }

  function show(Institution $institution, SessionResult $sessionResult)
  {
    return inertia(
      'institutions/session-result-sheets/session-result-template-1',
      [
        'sessionResult' => $sessionResult->load(
          'student.user',
          'academicSession',
          'student',
          'classification'
        ),
        'termResultDetails' => $this->getTermResultDetails($sessionResult),
        'resultCommentTemplate' => ResultCommentTemplate::getTemplate(false)
      ]
    );
  }

  private function getTermResultDetails(SessionResult $sessionResult)
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
    return $termResultDetails;
  }

  function classSessionResultSheet(
    Institution $institution,
    Classification $classification,
    ?AcademicSession $academicSession = null
  ) {
    abort_unless(
      currentInstitutionUser()?->isAdmin(),
      403,
      'You are not allowed to view this page'
    );
    $settingsHandler = SettingsHandler::makeFromRoute();
    $sessionResults = SessionResult::query()
      ->where('classification_id', $classification->id)
      ->where(
        'academic_session_id',
        $academicSession?->id ?? $settingsHandler->getCurrentAcademicSession()
      )
      ->with('student.user', 'academicSession', 'classification')
      ->take(100)
      ->get();
    return inertia('institutions/session-result-sheets/class-session-results', [
      'classSessionResults' => $sessionResults->map(function ($sessionResult) {
        return [
          'sessionResult' => $sessionResult,
          'termResultDetails' => $this->getTermResultDetails($sessionResult)
        ];
      }),
      'resultCommentTemplate' => ResultCommentTemplate::getTemplate(false),
      'classification' => $classification
    ]);
  }

  function showSessionCourseResult(
    Institution $institution,
    AcademicSession $academicSession,
    Classification $classification
  ) {
    abort_unless(currentInstitutionUser()->isStaff(), 403);
    $obj = new GenerateCourseSessionResult($classification, $academicSession);

    return inertia('institutions/session-result-sheets/course-session-result', [
      'courseSessionResults' => $obj->getCourseSessionResults(),
      'courses' => $obj->getRelatedCourses(),
      'academicSession' => $academicSession,
      'classification' => $classification
    ]);
  }

  function destroy(Institution $institution, SessionResult $sessionResult)
  {
    abort_unless(currentUser()->isInstitutionAdmin(), 403);
    $sessionResult->delete();

    return $this->ok();
  }
}
