<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Actions\CourseResult\RecordClassSheet;
use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordCourseResultRequest;
use App\Http\Requests\UploadClassSheetRequest;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use App\Support\Audit\AcademicIntegrityActivityLogger;
use App\Support\Audit\ModelAudit;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class CourseResultsController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Teacher
    ]);
  }

  private function validateUser(?CourseTeacher $courseTeacher)
  {
    $msg = 'You cannot record result for this course';
    abort_unless($courseTeacher, 403, $msg);
    $teacher = $courseTeacher->user;
    $user = currentUser();
    abort_if(
      !$user->isInstitutionAdmin() && !$teacher->is(currentUser()),
      403,
      $msg
    );
  }

  public function index(Institution $institution, Request $request)
  {
    $query = CourseResult::query()->select('course_results.*');
    CourseResultsUITableFilters::make($request->all(), $query)
      ->joinStudent()
      ->filterQuery()
      ->orderByCourseOrder()
      ->getQuery()
      ->oldest('users.last_name');

    return Inertia::render('institutions/courses/list-course-results', [
      'courseResults' => paginateFromRequest(
        $query
          ->with(
            'academicSession',
            'student',
            'teacher',
            'course',
            'classification'
          )
          ->latest('course_results.id')
      )
    ]);
  }

  public function create(
    Institution $institution,
    CourseTeacher $courseTeacher,
    Request $request
  ) {
    $courseTeacher->load(['course', 'user', 'classification']);
    $this->validateUser($courseTeacher);

    $settings = SettingsHandler::makeFromRoute(true);
    $selection = $this->getCreatePageSelection($request, $settings);
    [$student, $courseResult] = $this->getSelectedStudentAndResult(
      $request,
      $courseTeacher,
      $selection
    );

    return Inertia::render('institutions/courses/record-course-result', [
      'courseTeacher' => $courseTeacher,
      'courseResult' => $courseResult,
      'selectedStudent' => $student,
      'academic_session_id' => $selection['academic_session_id'],
      'term' => $selection['term'],
      'for_mid_term' => $selection['for_mid_term'],
      'courseResults' => paginateFromRequest(
        $this->getCourseResultListQuery($courseTeacher, $selection)
      ),
      'assessmentGroups' => Assessment::getAssessmentGroups(
        $selection['term'],
        $courseTeacher->classification_id
      ),
      'showExamInput' => $this->getShowExamInput($settings),
      'teachersCourses' => $courseTeacher->otherTeacherCourses()
    ]);
  }

  public function edit(Institution $institution, CourseResult $courseResult)
  {
    $courseResult->load('academicSession', 'student.user');
    $courseTeacher = $courseResult
      ->courseTeacherQuery()
      ->with('user', 'course', 'classification')
      ->first();

    $this->validateUser($courseTeacher);

    $settings = SettingsHandler::makeFromRoute(true);
    $assessmentGroups = Assessment::getAssessmentGroups(
      $courseResult->term,
      $courseTeacher->classification_id
    );
    return Inertia::render('institutions/courses/record-course-result', [
      'courseTeacher' => $courseTeacher,
      'courseResult' => $courseResult,
      'courseResults' => paginateFromRequest(
        $this->getCourseResultListQuery($courseTeacher, $courseResult)
      ),
      'assessmentGroups' => $assessmentGroups,
      'showExamInput' => $this->getShowExamInput($settings),
      'teachersCourses' => $courseTeacher->otherTeacherCourses()
    ]);
  }

  private function getCourseResultListQuery(
    CourseTeacher $courseTeacher,
    array|CourseResult $selection
  ) {
    if ($selection['for_mid_term'] === null) {
      return CourseResult::where('id', 0);
    }
    return $courseTeacher
      ->courseResultQuery()
      ->where(
        collect($selection)
          ->only(['academic_session_id', 'term', 'for_mid_term'])
          ->toArray()
      )
      ->with('academicSession', 'course', 'student.user', 'classification')
      ->latest('updated_at');
  }

  private function getCreatePageSelection(
    Request $request,
    SettingsHandler $settings
  ): array {
    return [
      'academic_session_id' => $request->integer(
        'academic_session_id',
        $settings->getCurrentAcademicSession()
      ),
      'term' => $request->input('term', $settings->getCurrentTerm()),
      'for_mid_term' => $this->getNullableBoolean($request, 'for_mid_term')
    ];
  }

  private function getNullableBoolean(Request $request, string $key): ?bool
  {
    if (!$request->has($key)) {
      return null;
    }

    return $request->boolean($key);
  }

  private function getSelectedStudentAndResult(
    Request $request,
    CourseTeacher $courseTeacher,
    array $selection
  ): array {
    if (!$request->filled('student_id')) {
      return [null, null];
    }

    $student = Student::query()
      ->with('user', 'classification')
      ->where('classification_id', $courseTeacher->classification_id)
      ->find($request->integer('student_id'));

    if (!$student || $selection['for_mid_term'] === null) {
      return [$student, null];
    }

    return [
      $student,
      $this->getSelectedCourseResult($courseTeacher, $student, $selection)
    ];
  }

  private function getSelectedCourseResult(
    CourseTeacher $courseTeacher,
    Student $student,
    array $selection
  ): ?CourseResult {
    return CourseResult::query()
      ->with('academicSession', 'student.user')
      ->where('course_id', $courseTeacher->course_id)
      ->where('classification_id', $courseTeacher->classification_id)
      ->where('student_id', $student->id)
      ->where('academic_session_id', $selection['academic_session_id'])
      ->where('term', $selection['term'])
      ->where('for_mid_term', $selection['for_mid_term'])
      ->first();
  }

  private function getShowExamInput(SettingsHandler $settings): array
  {
    return [
      'fullTerm' => $settings->shouldDisplayExamResults(null, false),
      'midTerm' => $settings->shouldDisplayExamResults(null, true)
    ];
  }

  public function store(
    RecordCourseResultRequest $request,
    Institution $institution,
    CourseTeacher $courseTeacher
  ) {
    $this->validateUser($courseTeacher);
    RecordCourseResult::run(
      [...Arr::except($request->validated(), 'result'), ...$request->result[0]],
      $courseTeacher,
      true
    );

    return response()->json(['ok' => true]);
  }

  public function upload(
    RecordCourseResultRequest $request,
    Institution $institution,
    CourseTeacher $courseTeacher
  ) {
    $this->validateUser($courseTeacher);
    $baseData = $request->safe()->except('result');
    $resultData = $request->safe()->result;

    $lastKey = array_key_last($resultData);
    DB::beginTransaction();
    foreach ($resultData as $key => $result) {
      RecordCourseResult::run(
        [...$baseData, ...$result],
        $courseTeacher,
        $key == $lastKey
      );
    }
    DB::commit();

    return response()->json(['ok' => true]);
  }

  public function uploadClassSheetView(Institution $institution)
  {
    return Inertia::render('institutions/courses/upload-class-sheet');
  }

  public function uploadClassSheetStore(
    Institution $institution,
    UploadClassSheetRequest $request
  ) {
    $data = $request->validated();
    $classificationId = $data['classification_id'];
    $classification = Classification::query()->findOrFail($classificationId);

    (new RecordClassSheet(
      $institution,
      $data,
      currentUser(),
      $classification
    ))->run();

    return response()->json(['ok' => true]);
  }

  public function destroy(
    Request $request,
    Institution $institution,
    CourseResult $courseResult
  ) {
    $currentUser = currentUser();
    abort_unless(
      $currentUser->isInstitutionAdmin() ||
        $courseResult->teacher_user_id == $currentUser->id,
      403
    );
    $courseResult->load([
      'student' => fn($q) => $q->withTrashed(),
      'student.classification'
    ]);
    $classification = $courseResult->student->classification;

    $courseId = $courseResult->course_id;
    $academicSessionId = $courseResult->academic_session_id;
    $term = $courseResult->term->value;
    $forMidTerm = $courseResult->for_mid_term;

    ClassResultInfo::ensureResultIsUnlocked(
      $courseResult->classification_id,
      $academicSessionId,
      $term,
      $forMidTerm
    );

    app(AcademicIntegrityActivityLogger::class)->resultScoreDeleted(
      $courseResult
    );
    ModelAudit::withoutAuditingFor(CourseResult::class, function () use (
      $courseResult
    ) {
      $courseResult->delete();
    });

    if ($classification) {
      EvaluateCourseResultForClass::run(
        $classification,
        $courseId,
        $academicSessionId,
        $term,
        $forMidTerm
      );
    }

    return $this->ok();
  }
}
