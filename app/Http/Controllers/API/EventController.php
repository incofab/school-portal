<?php
namespace App\Http\Controllers\API;

use App\Models\Exam;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Institution;
use App\Models\ExamSubject;

class EventController extends Controller
{
  private $resultsDir = '../public/files/';

  private $eventHelper;

  function __construct(\App\Helpers\EventHelper $eventHelper)
  {
    $this->eventHelper = $eventHelper;
  }

  function index(Request $request)
  {
    $institutionCode = $request->input('institution_code');

    $institution = Institution::whereCode($institutionCode)->first();

    $ret = $this->eventHelper->list(
      null,
      $institution->id,
      $this->numPerPage,
      $this->page
    );

    return $this->emitResponseRet($ret);
  }

  function downloadEventContent(
    \App\Helpers\EventContentHelper $eventContentHelper,
    Request $request
  ) {
    $ret = $eventContentHelper->downloadEventContent($request->all());

    return $this->emitResponseRet($ret);
  }

  function uploadEventResult(Request $request)
  {
    $exams = $request->input('exams');

    DB::beginTransaction();

    foreach ($exams as $exam) {
      $examSubjects = $exam['exam_subjects'];

      foreach ($examSubjects as $examSubject) {
        ExamSubject::find($examSubject['id'])->update($examSubject);
      }

      Exam::find($exam['id'])->update($exam);
    }

    DB::commit();

    return $this->emitResponseRet(retS('Exam records updated'));
  }
}
