<?php
namespace App\Http\Controllers\CCD\CourseSession;

use App\Http\Controllers\Controller;
use App\Models\CourseSession;
use App\Models\Institution;
use App\Models\Passage;

class PassageController extends Controller
{
  function index(
    Institution $institution,
    CourseSession $courseSession,
    Passage $passage = null
  ) {
    return view('ccd/course-sessions/passages', [
      'allRecords' => $courseSession->passages()->paginate(100),
      'courseSession' => $courseSession,
      'edit' => $passage
    ]);
  }

  function store(Institution $institution, CourseSession $courseSession)
  {
    $data = request()->validate(Passage::createRule());

    $courseSession
      ->passages()
      ->create([...$data, 'institution_id' => $institution->id]);

    return $this->res(
      successRes('Passage created'),
      instRoute('passages.index', [$courseSession])
    );
  }

  function update(Institution $institution, Passage $passage)
  {
    $data = request()->validate(Passage::createRule($passage));

    $passage->fill($data)->save();

    return $this->res(
      successRes('Passage record updated'),
      instRoute('passages.index', [$passage->course_session_id])
    );
  }

  function destroy(Institution $institution, Passage $passage)
  {
    $passage->delete();

    return $this->res(
      successRes('Passage record deleted'),
      instRoute('passages.index')
    );
  }
}
