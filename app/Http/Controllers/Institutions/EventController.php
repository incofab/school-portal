<?php
namespace App\Http\Controllers\Institutions;

use App\Models\Course;
use App\Models\Event;
use App\Models\Exam;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\EventSubject;

class EventController extends Controller
{
  private $eventsHelper;
  private $resultsDir = '../public/';

  function __construct(\App\Helpers\EventHelper $eventsHelper)
  {
    $this->eventsHelper = $eventsHelper;
  }

  function index($institutionId)
  {
    $ret = $this->eventsHelper->list(null, $institutionId);

    return $this->view('institution.event.index', [
      'allRecords' => $ret['result'],
      'count' => $ret['count']
    ]);
  }

  function create($institutionId)
  {
    return $this->view('institution.event.create', [
      'subjects' => Course::with('sessions')->get()
    ]);
  }

  function store($institutionId, Request $request)
  {
    DB::beginTransaction();

    $duration =
      (int) $request->input('hours', 0) * 60 * 60 +
      ((int) $request->input('minutes', 0)) * 60 +
      (int) $request->input('seconds', 0);

    $request->merge(['duration' => $duration]);

    $ret = Event::insert($request->all());

    if (!$ret[SUCCESSFUL]) {
      DB::rollBack();

      return $this->redirect(redirect()->back(), $ret);
    }

    /** @var \App\Models\Event $event */
    $event = $ret['data'];

    $post = [];
    $post['event_id'] = $event['id'];
    $post['course_session_id'] = $request->input('course_session_id');

    $ret = EventSubject::multiSubjectInsert($post);

    if (!$ret['success']) {
      DB::rollBack();

      return $this->redirect(redirect()->back(), $ret);
    }

    DB::commit();

    return redirect(route('institution.event.index', [$institutionId]))->with(
      'message',
      $ret[MESSAGE]
    );
  }

  function edit($institutionId, $tableId)
  {
    /** @var Event $oldEvent */
    $oldEvent = Event::where('id', '=', $tableId)
      ->with(['eventSubjects'])
      ->firstOrFail();
    //        dDie($oldEvent->toArray());
    if (!$oldEvent) {
      return redirect(route('institution.event.index', $institutionId))->with(
        'error',
        'Event record not found'
      );
    }

    $activeExam = Exam::whereEvent_id($oldEvent->id)
      ->whereNotNull('start_time')
      ->whereStatus('active')
      ->first();

    if ($activeExam) {
      return redirect(route('institution.event.index', $institutionId))->with(
        'error',
        'Cannot edit event because it contains some active exam(s)'
      );
    }

    $splitTime = \App\Core\Settings::splitTime($oldEvent['duration']);
    $oldEvent['hours'] = $splitTime['hours'];
    $oldEvent['minutes'] = $splitTime['minutes'];
    $oldEvent['seconds'] = $splitTime['seconds'];
    $eventSubjects = $oldEvent['eventSubjects'];
    $selectedSessionIDs = [];

    foreach ($eventSubjects as $eventSubject) {
      $selectedSessionIDs[] = $eventSubject['course_session_id'];
    }

    return $this->view('institution.event.edit', [
      'subjects' => Course::all(),
      'selectedSessionIDs' => $selectedSessionIDs,
      'edit' => true,
      'oldEvent' => $oldEvent
    ]);
  }

  function update($institutionId, $tableId, Request $request)
  {
    $duration =
      $request->input('hours', 0) * 60 * 60 +
      $request->input('minutes', 0) * 60 +
      $request->input('seconds', 0);

    $post = $request->all();
    $post['duration'] = $duration;
    $post['id'] = $tableId;

    DB::beginTransaction();

    $ret = Event::edit($post);

    if (!$ret[SUCCESSFUL]) {
      DB::rollBack();
      return $this->redirect(redirect()->back(), $ret);
    }

    /** @var \App\Models\Event $event */
    $event = $ret['data'];

    // Delete previous event subjects
    EventSubject::where('event_id', '=', $event['id'])->delete();

    $post = [];
    $post['event_id'] = $event['id'];
    $post['course_session_id'] = $request->input('course_session_id');

    $ret = EventSubject::multiSubjectInsert($post);

    if (!$ret[SUCCESSFUL]) {
      DB::rollBack();
      return $this->redirect(redirect()->back(), $ret);
    }

    DB::commit();

    return redirect(route('institution.event.index', $institutionId))->with(
      'success',
      $ret[MESSAGE]
    );
  }

  function suspend($institutionId, $table_id)
  {
    Event::whereId($table_id)
      ->whereInstitution_id($institutionId)
      ->update(['status' => 'suspended']);

    return redirect(route('institution.event.index', $institutionId))->with(
      'message',
      'Event has been suspended'
    );
  }

  function unSuspend($institutionId, $table_id)
  {
    Event::whereId($table_id)
      ->whereInstitution_id($institutionId)
      ->update(['status' => 'active']);

    return redirect(route('institution.event.index', $institutionId))->with(
      'message',
      'Event has been unsuspended'
    );
  }

  function destroy($institutionId, $table_id)
  {
    $activeExam = Exam::whereEvent_id($table_id)
      ->whereNotNull('start_time')
      ->whereStatus('active')
      ->first();

    if ($activeExam) {
      return redirect(route('institution.event.index', $institutionId))->with(
        'message',
        'Cannot delete event because it already contains some exam(s)'
      );
    }

    $event = Event::whereId($table_id)
      ->whereInstitution_id($institutionId)
      ->firstOrFail();

    Exam::whereEvent_id($event->id)->delete();
    $event->delete();

    return redirect(route('institution.event.index', $institutionId))->with(
      'message',
      'Event deleted successfully'
    );
  }

  function pauseAllExams($institutionId, $eventId)
  {
    $this->eventsHelper->pauseAllExam($eventId);

    return redirect(
      route('institution.exam.index', [$institutionId, $eventId])
    )->with('message', 'Event deleted successfully');
  }

  function eventResult($institutionId, $event_id)
  {
    $ret = $this->eventsHelper->getEventResult($event_id, $institutionId);

    if (!$ret[SUCCESSFUL]) {
      return redirect(route('institution.event.index', $institutionId))->with(
        'message',
        $ret[MESSAGE]
      );
    }

    return $this->view('institution.event.event_result', [
      'allRecords' => $ret['result_list'],
      'event' => $ret['event']
    ]);
  }

  function show($institutionId, $event_id)
  {
    $event = Event::where('id', '=', $event_id)
      ->with(['eventSubjects', 'eventSubjects.course', 'eventSubjects.session'])
      ->first();

    if (!$event) {
      return redirect(route('institution.event.index', $institutionId))->with(
        'message',
        'Event not found'
      );
    }

    return $this->view('institution.event.show', [
      'event' => $event,
      'eventSubjects' => $event['eventSubjects']
    ]);
  }

  /** @deprecated*/
  function smsEventResult($event_id)
  {
    if (!$_POST) {
      /** @var \App\Models\Event $event */
      $event = $this->eventsModel
        ->where(TABLE_ID, '=', $event_id)
        ->where(CENTER_CODE, '=', $this->getCenterData()[CENTER_CODE])
        ->first();

      if (!$event) {
        $this->session->flash('error', 'Event not found');

        redirect_(getAddr('center_view_all_events'));
      }

      return $this->view('centers/events/sms_event_result', [
        'event' => $event,
        'post' => $this->session->getFlash('post', [])
      ]);
    }

    ini_set('max_execution_time', 960);

    $ret = $this->eventsHelper->smsResult(
      $event_id,
      array_get($_POST, USERNAME),
      array_get($_POST, PASSWORD),
      $this->getCenterData()[CENTER_CODE],
      getAddr('center_view_all_events')
    );

    if (!$ret[SUCCESSFUL]) {
      $this->session->flash('error', $ret[MESSAGE]);

      $this->session->flash('post', $_POST);

      redirect_(null);
    }

    $this->session->flash(SUCCESSFUL, $ret[MESSAGE]);

    redirect_(getAddr('center_view_all_events'));
  }
  /** @deprecated*/
  function smsInvite($event_id)
  {
    /** @var \App\Models\Event $event */
    $event = $this->eventsModel
      ->where(TABLE_ID, '=', $event_id)
      ->where(CENTER_CODE, '=', $this->getCenterData()[CENTER_CODE])
      ->first();

    if (!$event) {
      $this->session->flash('error', 'Event not found');

      redirect_(getAddr('center_view_all_events'));
    }

    if (!$_POST) {
      return $this->view('centers/events/sms_invite', [
        'event' => $event,
        'post' => $this->session->getFlash('post', [])
      ]);
    }

    ini_set('max_execution_time', 960);

    $ret = $this->eventsHelper->smsInvite(
      $event,
      array_get($_POST, USERNAME),
      array_get($_POST, PASSWORD),
      array_get($_POST, 'time'),
      getAddr('center_view_all_events')
    );

    if (!$ret[SUCCESSFUL]) {
      $this->session->flash('error', $ret[MESSAGE]);

      $this->session->flash('post', $_POST);

      redirect_(null);
    }

    $this->session->flash(SUCCESSFUL, $ret[MESSAGE]);

    redirect_(getAddr('center_view_all_events'));
  }

  function downloadEventResult($institutionId, $event_id)
  {
    $ret = $this->eventsHelper->getEventResult($event_id, $institutionId);

    if (!$ret[SUCCESSFUL]) {
      return redirect(route('institution.event.index', $institutionId))->with(
        'message',
        'Event not found'
      );
    }

    $resultList = $ret['result_list'];

    $event = $ret['event'];

    $headers = [
      'S/No',
      'Name',
      'Student ID',
      'Exam No',
      'Subjects',
      'Correct Answers',
      'Score'
    ];

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(
      $this->resultsDir . 'resultsheet-template.xlsx'
    );

    $sheetData = $spreadsheet->getActiveSheet();

    $i = 1;
    foreach ($headers as $value) {
      $sheetData->setCellValueByColumnAndRow($i, 1, $value);
      $i++;
    }

    $eventSubjects = $event->eventSubjects;
    foreach ($eventSubjects as $eventSubject) {
      $sheetData->setCellValueByColumnAndRow(
        $i,
        1,
        $eventSubject->course->course_code
      );
      $i++;
    }

    $j = 2;
    $serialNo = 1;
    foreach ($resultList as $result) {
      $sheetData->setCellValueByColumnAndRow(1, $j, $serialNo);
      $sheetData->setCellValueByColumnAndRow(2, $j, $result['name']);
      $sheetData->setCellValueByColumnAndRow(3, $j, $result['student_id']);
      $sheetData->setCellValueByColumnAndRow(4, $j, $result['exam_no']);
      $sheetData->setCellValueByColumnAndRow(5, $j, $result['subjects']);
      $sheetData->setCellValueByColumnAndRow(
        6,
        $j,
        "{$result['total_score']}/{$result['total_num_of_questions']}"
      );
      $sheetData->setCellValueByColumnAndRow(
        7,
        $j,
        "{$result['total_score_percent']}/{$result['total_num_of_questions_percent']}"
      );

      $i = 8;
      $subjectsAndScores = $result['subjects_and_scores'];
      foreach ($eventSubjects as $eventSubject) {
        $courseCode = $eventSubject->course->course_code;

        if (!empty($subjectsAndScores[$courseCode])) {
          $sheetData->setCellValueByColumnAndRow(
            $i,
            $j,
            $subjectsAndScores[$courseCode]['score']
          );
        }

        $i++;
      }

      $j++;
      $serialNo++;
    }

    $title = str_replace(' ', '_', $event['title']);

    $fileName = "$title--{$event['id']}--results.xlsx";

    // Output the file so that user can download
    header('Content-Type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=$fileName");
    header('Cache-Control:max-age=0');

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter(
      $spreadsheet,
      'Xlsx'
    );
    $writer->save('php://output');

    exit();
  }
}
