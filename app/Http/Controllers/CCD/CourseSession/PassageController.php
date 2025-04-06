<?php
namespace App\Http\Controllers\CCD\CourseSession;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Passage;
use App\Models\Support\QuestionCourseable;
use App\Support\MorphableHandler;

class PassageController extends Controller
{
  function index(
    Institution $institution,
    QuestionCourseable $morphable,
    ?Passage $passage = null
  ) {
    return view('ccd/course-sessions/passages', [
      'allRecords' => $morphable->passages()->paginate(100),
      'courseable' => $morphable,
      'edit' => $passage
    ]);
  }

  function store(Institution $institution, QuestionCourseable $morphable)
  {
    $data = request()->validate(Passage::createRule());

    $morphable
      ->passages()
      ->create([...$data, 'institution_id' => $institution->id]);

    return $this->res(
      successRes('Passage created'),
      instRoute('passages.index', [$morphable->getMorphedId()])
    );
  }

  function update(Institution $institution, Passage $passage)
  {
    $data = request()->validate(Passage::createRule($passage));

    $passage->fill($data)->save();

    return $this->res(
      successRes('Passage record updated'),
      instRoute('passages.index', [
        MorphableHandler::make()->buildIdFromCourseable($passage)
      ])
    );
  }

  function destroy(Institution $institution, Passage $passage)
  {
    $passage->delete();

    return $this->res(
      successRes('Passage record deleted'),
      instRoute('passages.index', [
        MorphableHandler::make()->buildIdFromCourseable($passage)
      ])
    );
  }
}
