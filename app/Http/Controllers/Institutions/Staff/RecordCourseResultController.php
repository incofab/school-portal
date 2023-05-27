<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\RecordCourseResult;
use App\Enums\UserRoleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordCourseResultRequest;
use App\Models\CourseTeacher;
use App\Models\CourseResult;
use Inertia\Inertia;

class RecordCourseResultController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([UserRoleType::Admin, UserRoleType::Teacher]);
  }

  private function validateUser(CourseTeacher $courseTeacher)
  {
    $teacher = $courseTeacher->teacher;
    abort_if(
      !$teacher->isAdmin() && !$teacher->is(currentUser()),
      403,
      'You cannot record result for this course'
    );
  }

  public function create(CourseTeacher $courseTeacher)
  {
    $courseTeacher->load(['course', 'teacher']);
    $this->validateUser($courseTeacher);
    return Inertia::render('institutions/staff/record-student-course-result', [
      'courseTeacher' => $courseTeacher
    ]);
  }

  public function edit(CourseResult $studentCourseResult)
  {
    $courseTeacher = CourseTeacher::where(
      'course_id',
      $studentCourseResult->course_id
    )
      ->where('user_id', $studentCourseResult->teacher_user_id)
      ->first();

    $this->validateUser($courseTeacher);

    return Inertia::render('institutions/staff/record-student-course-result', [
      'courseTeacher' => $courseTeacher
    ]);
  }

  public function store(
    RecordCourseResultRequest $request,
    CourseTeacher $courseTeacher
  ) {
    $this->validateUser($courseTeacher);

    RecordCourseResult::run($request->validated(), $courseTeacher);

    return response()->json(['ok' => true]);
  }
}
