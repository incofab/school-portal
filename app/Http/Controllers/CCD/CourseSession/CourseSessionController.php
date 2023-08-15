<?php
namespace App\Http\Controllers\CCD\CourseSession;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Institution;

class CourseSessionController extends Controller
{
  function index(Institution $institution, Course $course = null)
  {
    $query = $course ? $course->sessions() : CourseSession::query();

    return view('ccd/course-sessions/index', [
      'allRecords' => $query
        ->with('course')
        ->withCount(['questions'])
        ->paginate(100),
      'course' => $course,
      'courses' => Course::all()
    ]);
  }

  function create(Institution $institution, Course $course)
  {
    return view('ccd/course-sessions/create', [
      'edit' => null,
      'course' => $course
    ]);
  }

  function store(Institution $institution, Course $course)
  {
    $data = request()->validate(CourseSession::createRule());

    $course
      ->sessions()
      ->getQuery()
      ->updateOrCreate(
        [
          'institution_id' => $institution->id,
          'course_id' => $course->id,
          'session' => $data['session']
        ],
        $data
      );

    return $this->res(
      successRes('Course session record created'),
      instRoute('course-sessions.index', [$course])
    );
  }

  function edit(Institution $institution, CourseSession $courseSession)
  {
    return view('ccd/course-sessions/create', [
      'edit' => $courseSession,
      'course' => $courseSession->course
    ]);
  }

  function update(Institution $institution, CourseSession $courseSession)
  {
    $data = request()->validate(CourseSession::createRule($courseSession));

    $courseSession->fill($data)->save();

    return $this->res(
      successRes('Course session record updated'),
      instRoute('course-sessions.index', [$courseSession->course_id])
    );
  }

  function destroy(Institution $institution, CourseSession $courseSession)
  {
    dd('Not implemented');
    $courseSession->delete();

    return $this->res(
      successRes('Course session record deleted'),
      instRoute('course-sessions.index')
    );
  }
}
