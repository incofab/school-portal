<?php
namespace App\Http\Controllers\Exam;

use Illuminate\Http\Request;
use App\Helpers\ExamHandler;

class ExamController extends BaseExamController
{
  protected $examRepository;

  function __construct(\App\Helpers\ExamRepository $examRepository)
  {
    $this->examRepository = $examRepository;
  }

  function startExam(Request $request, $examNo = null)
  {
    $examNo = $request->input('exam_no', $examNo);

    if (!$examNo) {
      return $this->view('exam.exam-login');
    }

    $ret = $this->examRepository->startExam($examNo);

    if (!$ret[SUCCESSFUL]) {
      return $this->redirect(redirect()->back(), $ret);
    }

    $exam = $ret['exam'];
    $student = $exam['student'];

    $studentDataFormated = [
      'firstname' => $student['firstname'],
      'lastname' => $student['lastname'],
      'student_id' => $exam['student_id'],
      'token' => $exam['id'],
      'exam_no' => $exam['exam_no']
    ];

    $pageData = [
      'exam_data' => $ret['data'],
      'data' => $studentDataFormated
    ];
    // 	    dDie($pageData);
    return $this->view('exam.react-exam-page', [
      'examData' => $pageData,
      'eventId' => $exam->event_id
      // 		    'student_data' => $studentDataFormated,
    ]);
  }

  function pauseExam(Request $request)
  {
    $examNo = $request->input('exam_no');
    $ret = $this->examRepository->pauseExam($examNo);

    return response()->json($ret);
  }

  function endExam(Request $request)
  {
    $examNo = $request->input('exam_no');

    $ret = $this->examRepository->endExam($examNo);

    return response()->json($ret);
  }

  function submitExam(Request $request, ExamHandler $examHandler)
  {
    $examNo = $request->input('exam_no');
    $eventId = $request->input('event_id');

    /*** Handle pending question attempts ****/
    $allAttempts = $request->input('attempts', []);

    $examHandler->attemptQuestion($allAttempts, $eventId, $examNo);

    /*** // Handle pending question attempts****/

    /*** End exam ****/
    $ret = $this->examRepository->endExam($examNo);

    return response()->json($ret);
  }

  function examCompleted($examNo = null)
  {
    return $this->view('exam.exam-completed');
  }

  function viewResultForm()
  {
    return $this->view('exam.view-result-form');
  }

  function viewResult(Request $request)
  {
    $examNo = $request->input('exam_no');
    if (!$examNo) {
      return redirect(route('home.exam.view-result-form'))->with(
        'message',
        'Exam No not found'
      );
    }

    $exam = \App\Models\Exam::where('exam_no', '=', $examNo)
      ->with([
        'examSubjects',
        'examSubjects.course',
        'student',
        'event',
        'event.institution'
      ])
      ->first();

    if (!$exam) {
      return redirect(route('home.exam.start'))->with(
        'message',
        'Exam not found'
      );
    }

    if ($exam->status !== 'ended' && $exam->status !== 'expired') {
      return redirect(route('home.exam.start'))->with(
        'message',
        'Exam has not been concluded'
      );
    }

    $event = $exam->event;

    $examSubjects = $exam['examSubjects'];
    $totalScorePercent = 0;

    foreach ($examSubjects as $examSubject) {
      $course = $examSubject->course;
      $score = $examSubject['score'];
      $numOfQuestions = $examSubject['num_of_questions'];

      $scorePercent = \App\Core\Settings::getPercentage(
        $score,
        $numOfQuestions,
        0
      );

      $result[$examSubject['id']] = [
        'number_of_questions' => $numOfQuestions,

        'course_title' => $course['title'] ?? $course['code'],

        'score' => $score,

        'score_percent' => $scorePercent
      ];

      $subjectsCourseCode[] = $course['code'];

      $totalScorePercent += $scorePercent;
    }

    return $this->view('exam.view-result', [
      'result_detail' => $result,
      'exam' => $exam,
      'event' => $event,
      'institution' => $event->institution,
      'student' => $exam->student,
      'examSubjects' => $examSubjects,
      'subjectsCourseCode' => $subjectsCourseCode,
      'total_score_percent' => $totalScorePercent
    ]);
  }
}
