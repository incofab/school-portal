<?php
namespace App\Http\Controllers\CCD\CourseSession;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Institution;
use Illuminate\Http\Request;

class CourseSessionController extends Controller
{
  function index(Institution $institution, ?Course $course = null)
  {
    $this->authorizeQuestionBank($course);
    $query = $course ? $course->courseSessions() : CourseSession::query();

    return view('ccd/course-sessions/index', [
      'allRecords' => $query
        ->with('course')
        ->withCount(['questions', 'theoryQuestions'])
        ->paginate(100),
      'course' => $course,
      'courses' => Course::all()
    ]);
  }

  function create(Institution $institution, Course $course)
  {
    $this->authorizeQuestionBank($course);
    return view('ccd/course-sessions/create', [
      'edit' => null,
      'course' => $course
    ]);
  }

  function store(Institution $institution, Course $course, Request $request)
  {
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

  function edit(Institution $institution, CourseSession $courseSession)
  {
    $this->authorizeQuestionBank($courseSession->course);
    return view('ccd/course-sessions/create', [
      'edit' => $courseSession,
      'course' => $courseSession->course
    ]);
  }

  function update(Institution $institution, CourseSession $courseSession)
  {
    $this->authorizeQuestionBank($courseSession->course);
    $data = request()->validate(CourseSession::createRule($courseSession));

    $courseSession->fill($data)->save();

    return $this->res(
      successRes('Course session record updated'),
      instRoute('course-sessions.index', [$courseSession->course])
    );
  }

  function destroy(Institution $institution, CourseSession $courseSession)
  {
    $this->authorizeQuestionBank($courseSession->course);
    $courseSession->delete();

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
