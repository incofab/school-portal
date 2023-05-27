<?php
namespace App\Http\Controllers\Institutions;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ExamController extends Controller
{
  private $examRepository;
  private $gradeRepository;

  function __construct(
    \App\Helpers\ExamRepository $examRepository,
    \App\Helpers\GradeRepository $gradeRepository
  ) {
    $this->examRepository = $examRepository;
    $this->gradeRepository = $gradeRepository;
  }

  function index($institutionId, $eventId = null)
  {
    if (empty($eventId)) {
      $eventIds = Event::whereInstitution_id($institutionId)->pluck('id');
    } else {
      $eventIds = Event::whereInstitution_id($institutionId)
        ->where('id', '=', $eventId)
        ->pluck('id');
    }

    $builder = Exam::with([
      'examSubjects',
      'examSubjects.session',
      'examSubjects.session.course',
      'student',
      'event'
    ])->whereIn('event_id', $eventIds);

    $allRecords = $builder
      ->skip($this->numPerPage * ($this->page - 1))
      ->orderBy('id', 'DESC')
      ->take($this->numPerPage)
      ->get();

    return $this->view('institution.exam.index', [
      'allRecords' => $allRecords,
      'allEvents' => Event::whereInstitution_id($institutionId)->get(),
      'count' => $builder->get()->count(),
      'eventId' => $eventId
    ]);
  }

  function create($institutionId, $studentId = null)
  {
    $events = Event::getActiveEvents($institutionId);

    if (!$events->first()) {
      return redirect(route('institution.event.index', $institutionId))->with(
        'message',
        'First create an event'
      );
    }

    return $this->view('institution.exam.create', [
      'events' => $events,
      'student_id' => $studentId,
      'student' => Student::whereStudent_id($studentId)->first()
    ]);
  }

  function store($institutionId, Request $request)
  {
    $eventId = $request->input('event_id');
    $studentId = $request->input('student_id');

    $selectedSubjectSessionIDs = $request->input('course_session_id');

    $ret = $this->examRepository->registerExam(
      $eventId,
      $studentId,
      $selectedSubjectSessionIDs,
      $request->get('institution')
    );

    if (!$ret[SUCCESSFUL]) {
      return $this->redirect(redirect()->back(), $ret);
    }

    return redirect(route('institution.exam.index', $institutionId))->with(
      'message',
      $ret[MESSAGE]
    );
  }

  function createGradeExam($institutionId, $gradeId = null)
  {
    $events = Event::getActiveEvents($institutionId);

    if (!$events->first()) {
      return redirect(route('institution.grade.index', $institutionId))->with(
        'message',
        'No active event created'
      );
    }

    return $this->view('institution.exam.create-grade-exam', [
      'events' => $events,
      'gradeId' => $gradeId,
      'allGrades' => $this->gradeRepository->list($institutionId)['all']
    ]);
  }

  function storeGradeExam($institutionId, Request $request)
  {
    $eventId = $request->input('event_id');
    $gradeId = $request->input('grade_id');

    $selectedSubjectSessionIDs = $request->input('course_session_id');
    $students = Student::whereGrade_id($gradeId)->get();

    $event = Event::whereId($eventId)->firstOrFail();

    foreach ($students as $student) {
      $this->examRepository->registerExam(
        $event,
        $student->student_id,
        $selectedSubjectSessionIDs,
        $request->get('institution')
      );
    }

    return redirect(route('institution.exam.index', $institutionId))->with(
      'message',
      'Exams recorded'
    );
  }

  function multiRegisterExam($institutionId, $eventId)
  {
    $event = Event::where('id', '=', $eventId)
      ->with(['eventSubjects', 'eventSubjects.course'])
      ->first();

    if (!$event) {
      return redirect(route('institution.event.index'))->with(
        'error',
        'Event not found'
      );
    }

    if ($event['institution_id'] != Auth::user()->getInstitution()->id) {
      return redirect(route('institution.event.index'))->with(
        'error',
        'This event does not belong to this center'
      );
    }

    if (!$_POST) {
      $alreadyRegisteredStudentsID = Exam::whereEvent_id($eventId)->lists(
        'student_id'
      );

      $eligibleStudents = Student::whereInstitution_id(
        Auth::user()->getInstitution()->id
      )
        ->whereNotIn('student_id', $alreadyRegisteredStudentsID)
        ->take(40)
        ->get();

      if (!$eligibleStudents->first()) {
        return redirect(route('institution.event.index'))->with(
          'error',
          'No more students to register'
        );
      }

      return $this->view('institution.exam.multi_register_exam', [
        'students' => $eligibleStudents,
        'event' => $event,
        'eventSubjects' => $event['eventSubjects']
      ]);
    }

    $students = Arr::get($_POST, 'student_id');
    $sessions = Arr::get($_POST, 'course_session_id');

    $len = count($students);

    ini_set('max_execution_time', 1440);

    for ($i = 0; $i < $len; $i++) {
      $studentId = $students[$i];

      if (empty($sessions[$i])) {
        continue;
      }

      $session = $sessions[$i];

      $this->examRepository->registerExam(
        $event,
        $studentId,
        $session,
        Auth::user()->getInstitution()
      );
    }

    return redirect(route('institution.exam.index'))->with(
      'message',
      'Exams recorded'
    );
  }

  function edit($institutionId, $tableId)
  {
    die('Not implemented, delete instead');

    if (!$_POST) {
      return $this->view('centers/students/add', [
        'post' => $this->session->getFlash(
          'post',
          $this->examsModel->where(TABLE_ID, '=', $tableId)->first()
        ),
        'edit' => true,
        'tableId' => $tableId
      ]);
    }

    $ret = $this->examsModel->edit($_POST, $this->data);

    if ($ret[SUCCESS]) {
      $this->session->flash('success', $ret[MESSAGE]);

      redirect_(getAddr('center_view_all_exams'));
    }

    $this->session->flash('val_errors', getValue($ret, 'val_errors'));
    $this->session->flash('post', $_POST);

    redirect_(getAddr(null));
  }

  function forceEndExam($institutionId, $examNo)
  {
    $exam = Exam::where('exam_no', '=', $examNo)
      ->with(['student'])
      ->first();

    if (!$exam) {
      return redirect(route('institution.exam.index'))->with(
        'error',
        'Exam not found'
      );
    }

    $student = $exam['student'];

    $ret = $this->examRepository->endExam($examNo, $student);

    return redirect(route('institution.exam.index'))->with(
      'message',
      $ret[MESSAGE]
    );
  }

  function destroy($institutionId, $table_id)
  {
    $ret = $this->verifyExamID($table_id);

    if (!$ret[SUCCESSFUL]) {
      return redirect(route('institution.exam.index'))->with(
        'error',
        $ret[MESSAGE]
      );
    }

    $ret['exam']->delete();

    return redirect(route('institution.exam.index'))->with(
      'message',
      $ret[MESSAGE]
    );
  }

  function extendExamTimeView($institutionId, $examNo)
  {
    $exam = Exam::whereExam_no($examNo)
      ->with('student', 'event')
      ->firstOrFail();

    $event = $exam->event;
    $startTime = \Carbon\Carbon::parse($exam['start_time']);
    $pausedTime = \Carbon\Carbon::parse($exam['pause_time']);
    $endTime = \Carbon\Carbon::parse($exam['end_time']);

    if ($exam['status'] === STATUS_PAUSED) {
      $timeElapsed = $startTime->diffInSeconds($pausedTime);
      $timeRemaining = $event['duration'] - $timeElapsed;
    } else {
      $timeRemaining = \Carbon\Carbon::now()->diffInSeconds($endTime, false);
    }

    if ($timeRemaining < 1) {
      $timeRemaining = 0;
    }

    return $this->view('institution.exam.extend_time', [
      'exam' => $exam,
      'student' => $exam->student,
      'event' => $event,
      'timeRemaining' => $timeRemaining
    ]);
  }

  function extendExamTime($institutionId, $examNo, Request $request)
  {
    $exam = Exam::whereExam_no($examNo)
      ->with(['student', 'event'])
      ->firstOrFail();

    if ($exam['status'] !== STATUS_PAUSED && empty($exam['end_time'])) {
      return redirect(route('institution.exam.index', $institutionId))->with(
        'error',
        'Exam has not started'
      );
    }

    $event = $exam['event'];

    $time = (int) $request->extend_time;

    $ret = $this->examRepository->extendExam($exam, $time);

    if (!$ret[SUCCESSFUL]) {
      return $this->redirect(redirect()->back(), $ret);
    }

    return redirect(route('institution.exam.index', $institutionId))->with(
      'message',
      $ret[MESSAGE]
    );
  }

  function viewExamResult($institutionId, $examNo, $studentID)
  {
    return $this->displayExamResult($examNo, $studentID);
  }

  private function verifyExamID($institutionId, $examID)
  {
    $exam = Exam::where('id', '=', $examID)
      ->with(['event'])
      ->first();

    if (!$exam) {
      return retF('Exam record not found');
    }

    $event = $exam['event'];

    if ($event['institution_id'] != Auth::user()->getInstitution()->id) {
      return retF('This exam does not exist in this center');
    }

    return [
      SUCCESSFUL => true,
      MESSAGE => '',
      'exam' => $exam,
      'event' => $event
    ];
  }
}
