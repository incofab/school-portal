<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordCourseResultRequest;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

  private function validateUser(CourseTeacher $courseTeacher)
  {
    $teacher = $courseTeacher->user;
    $user = currentUser();
    abort_if(
      !$user->isInstitutionAdmin() && !$teacher->is(currentUser()),
      403,
      'You cannot record result for this course'
    );
  }

  public function index(Institution $institution, Request $request)
  {
    $query = CourseResult::query()->select('course_results.*');
    CourseResultsUITableFilters::make($request->all(), $query)
      ->joinStudent()
      ->filterQuery()
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

  public function create(Institution $institution, CourseTeacher $courseTeacher)
  {
    $courseTeacher->load(['course', 'user', 'classification']);
    $this->validateUser($courseTeacher);
    $courseResultQuery = CourseResult::query()
      ->where('course_id', $courseTeacher->course_id)
      ->where('teacher_user_id', $courseTeacher->user_id)
      ->where('classification_id', $courseTeacher->classification_id)
      ->with('academicSession', 'course', 'student.user')
      ->latest('updated_at');

    return Inertia::render('institutions/courses/record-course-result', [
      'courseTeacher' => $courseTeacher,
      'courseResults' => paginateFromRequest($courseResultQuery),
      'assessments' => Assessment::query()->get()
    ]);
  }

  public function edit(Institution $institution, CourseResult $courseResult)
  {
    $courseResult->load('academicSession', 'student.user');
    $courseTeacher = CourseTeacher::where('course_id', $courseResult->course_id)
      ->where('user_id', $courseResult->teacher_user_id)
      ->with('user', 'course', 'classification')
      ->first();

    $this->validateUser($courseTeacher);

    return Inertia::render('institutions/courses/record-course-result', [
      'courseTeacher' => $courseTeacher,
      'courseResult' => $courseResult,
      'assessments' => Assessment::query()->get()
    ]);
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

    $courseResult->delete();

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
