<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\CourseResult\InsertResultFromRecordingSheet;
use App\Actions\CourseResult\RecordCourseResult;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecordCourseResultRequest;
use App\Models\CourseTeacher;
use App\Models\CourseResult;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use Illuminate\Http\Request;
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
    $teacher = $courseTeacher->teacher;
    abort_if(
      !$teacher->isInstitutionAdmin() && !$teacher->is(currentUser()),
      403,
      'You cannot record result for this course'
    );
  }

  public function index(Request $request)
  {
    $query = CourseResult::query();
    CourseResultsUITableFilters::make($request->all(), $query);
    return Inertia::render('institutions/courses/list-course-results', [
      'courseResults' => paginateFromRequest(
        $query->latest('course-results.id')
      )
    ]);
  }

  public function create(CourseTeacher $courseTeacher)
  {
    $courseTeacher->load(['course', 'teacher']);
    $this->validateUser($courseTeacher);
    return Inertia::render('institutions/staff/record-course-result', [
      'courseTeacher' => $courseTeacher
    ]);
  }

  public function edit(CourseResult $courseResult)
  {
    $courseTeacher = CourseTeacher::where('course_id', $courseResult->course_id)
      ->where('user_id', $courseResult->teacher_user_id)
      ->first();

    $this->validateUser($courseTeacher);

    return Inertia::render('institutions/staff/record-course-result', [
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

  public function upload(
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
