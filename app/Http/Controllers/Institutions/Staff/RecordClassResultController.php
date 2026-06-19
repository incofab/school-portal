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
    $forMidTerm = request()->boolean('for_mid_term', false);

    $setting = SettingsHandler::makeFromRoute(true);
    $students = Student::query()
      ->select('students.*')
      ->join('users', 'users.id', 'students.user_id')
      ->where('classification_id', $courseTeacher->classification_id)
      ->with(
        'courseResults',
        fn($q) => $q
          ->where([
            'academic_session_id' => $setting->getCurrentAcademicSession(),
            'term' => $setting->getCurrentTerm(),
            'classification_id' => $courseTeacher->classification_id,
            'course_id' => $courseTeacher->course_id
          ])
          ->whereIn('for_mid_term', [false, true])
          ->orderBy('for_mid_term')
      )
      ->with('user')
      ->latest('users.first_name')
      ->get();
    $assessmentGroups = Assessment::getAssessmentGroups(
      $setting->getCurrentTerm(),
      $courseTeacher->classification_id
    );

    return Inertia::render('institutions/courses/record-class-course-result', [
      'courseTeacher' => $courseTeacher,
      'assessmentGroups' => $assessmentGroups,
      'showExamInput' => [
        'fullTerm' => $setting->shouldDisplayExamResults(null, false),
        'midTerm' => $setting->shouldDisplayExamResults(null, true)
      ],
      'students' => $students,
      'teachersCourses' => $courseTeacher->otherTeacherCourses(),
      'forMidTerm' => $forMidTerm
    ]);
  }

  public function store(
    RecordCourseResultRequest $request,
    Institution $institution,
    CourseTeacher $courseTeacher
  ) {
    $baseData = $request->safe()->except('result');
    $resultData = $request->result ?? [];

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
