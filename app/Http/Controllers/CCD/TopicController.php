<?php
namespace App\Http\Controllers\CCD;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Topic;

class TopicController extends Controller
{
  function index(Institution $institution, Course $course = null)
  {
    return view('ccd/topics/index', [
      'allRecords' => Topic::query()
        ->when($course, fn($q) => $q->where('course_id', $course->id))
        ->paginate(100),
      'course' => $course
    ]);
  }

  function create(Institution $institution, Course $course)
  {
    return view('ccd/topics/create', ['course' => $course, 'edit' => null]);
  }

  function store(Institution $institution, Course $course)
  {
    $data = request()->validate(Topic::createRule());
    $course->topics()->create([...$data, 'institution_id' => $institution->id]);
    return $this->res(
      successRes('Topic created'),
      instRoute('topics.index', [$course])
    );
  }

  function edit(Institution $institution, Topic $topic)
  {
    return view('ccd/topics/create', [
      'edit' => $topic,
      'course' => $topic->course
    ]);
  }

  function update(Institution $institution, Topic $topic)
  {
    $data = request()->validate(Topic::createRule($topic));
    $topic->fill($data)->save();
    return $this->res(successRes('Topic updated'), instRoute('topics.index'));
  }

  function destroy(Institution $institution, Topic $topic)
  {
    abort_if(
      $topic->questions()->exists(),
      401,
      'Failed: Cannot delete a topic already assigned to a question'
    );
    $topic->delete();
    return $this->res(successRes('Topic deleted'), instRoute('topics.index'));
  }
}
