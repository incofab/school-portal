<?php

namespace App\Http\Controllers\CCD\CourseSession;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Institution;
use Illuminate\Http\Request;

class CourseSessionController extends Controller
{
  public function index(Institution $institution, ?Course $course = null)
  {
    $this->authorizeQuestionBank($course);
    $query = $course ? $course->courseSessions() : CourseSession::query();

    return view('ccd/course-sessions/index', [
      'allRecords' => $query
        ->with('course')
        ->withCount(['questions', 'theoryQuestions'])
        ->paginate(100),
      'course' => $course,
      'courses' => Course::query()
        ->orderedByCourseOrder()
        ->get()
    ]);
  }

  public function create(Institution $institution, Course $course)
  {
    $this->authorizeQuestionBank($course);

    return view('ccd/course-sessions/create', [
      'edit' => null,
      'course' => $course
    ]);
  }

  public function store(
    Institution $institution,
    Course $course,
    Request $request
  ) {
    $this->authorizeQuestionBank($course);
    $data = request()->validate(CourseSession::createRule());

    $course
      ->courseSessions()
      ->getQuery()
      ->updateOrCreate(
        [
          'institution_id' => $institution->id,
          'course_id' => $course->id,
          'session' => $data['session']
        ],
        $data
      );

    if ($request->expectsJson()) {
      return $this->ok(['new_course_data' => $course->load('sessions')]);
    }

    return $this->res(
      successRes('Course session record created'),
      instRoute('course-sessions.index', [$course])
    );
  }

  public function edit(Institution $institution, CourseSession $courseSession)
  {
    $this->authorizeQuestionBank($courseSession->course);

    return view('ccd/course-sessions/create', [
      'edit' => $courseSession,
      'course' => $courseSession->course
    ]);
  }

  public function update(Institution $institution, CourseSession $courseSession)
  {
    $this->authorizeQuestionBank($courseSession->course);
    $data = request()->validate(CourseSession::createRule($courseSession));

    $courseSession->fill($data)->save();

    return $this->res(
      successRes('Course session record updated'),
      instRoute('course-sessions.index', [$courseSession->course])
    );
  }

  public function destroy(
    Institution $institution,
    CourseSession $courseSession
  ) {
    $this->authorizeQuestionBank($courseSession->course);
    abort_if(
      $courseSession->hasExistingReferences(),
      400,
      'This course session has existing references. It cannot be deleted.'
    );
    $courseSession->forceDelete();

    return $this->res(
      successRes('Course session record deleted'),
      instRoute('course-sessions.index')
    );
  }

  private function authorizeQuestionBank(?Course $course): void
  {
    $this->authorize('viewQuestionBank', [Course::class, $course]);
  }
}
