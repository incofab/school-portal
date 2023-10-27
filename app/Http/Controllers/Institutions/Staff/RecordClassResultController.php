<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordCourseResultRequest;
use App\Models\Assessment;
use App\Models\CourseTeacher;
use App\Models\Institution;
use App\Models\Student;
use App\Support\SettingsHandler;
use DB;
use Inertia\Inertia;

class RecordClassResultController extends Controller
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

  public function create(Institution $institution, CourseTeacher $courseTeacher)
  {
    $courseTeacher->load(['course', 'user', 'classification']);
    $this->validateUser($courseTeacher);

    $setting = SettingsHandler::makeFromRoute();
    $students = Student::query()
      ->where('classification_id', $courseTeacher->classification_id)
      ->with(
        'courseResults',
        fn($q) => $q->where([
          ...$setting->academicQueryData(),
          'classification_id' => $courseTeacher->classification_id,
          'course_id' => $courseTeacher->course_id
        ])
      )
      ->with('user')
      ->get();
    return Inertia::render('institutions/courses/record-class-course-result', [
      'courseTeacher' => $courseTeacher,
      'assessments' => Assessment::query()->get(),
      'students' => $students
    ]);
  }

  public function store(
    RecordCourseResultRequest $request,
    Institution $institution,
    CourseTeacher $courseTeacher
  ) {
    $baseData = $request->safe()->except('result');
    $resultData = $request->safe()->result;

    DB::beginTransaction();
    foreach ($resultData as $result) {
      RecordCourseResult::run([...$baseData, ...$result], $courseTeacher);
    }
    DB::commit();

    EvaluateCourseResultForClass::run(
      $courseTeacher->classification,
      $courseTeacher->course_id,
      $request->academic_session_id,
      $request->term,
      $request->for_mid_term
    );

    return response()->json(['ok' => true]);
  }
}
