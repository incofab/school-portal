<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\InsertResultFromRecordingSheet;
use App\Enums\UserRoleType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordCourseResultRequest;
use App\Models\CourseTeacher;

class UploadCourseResultsController extends Controller
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

  public function store(
    RecordCourseResultRequest $request,
    CourseTeacher $courseTeacher
  ) {
    $this->validateUser($courseTeacher);

    InsertResultFromRecordingSheet::run(
      $request->file('file'),
      $request->all(),
      $courseTeacher
    );

    return response()->json(['ok' => true]);
  }
}
