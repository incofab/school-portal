<?php

namespace App\Http\Controllers\Home;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ExamContent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use App\Models\Exam;

class ExamController extends Controller
{
  private $examHelper;
  private $examHandler;

  public function __construct(
    //         \App\Helpers\ExamHelper $examHelper,
    \App\Helpers\ExamHandler $examHandler
  ) {
    //         $this->middleware('auth');
    //         $this->examHelper = $examHelper;
    $this->examHandler = $examHandler;
  }

  function index()
  {
    $ret = $this->examHelper->list(Auth::user()->id);

    // Show a list of Exam Content Bodies
    return view('user.exam.index', [
      'all' => $ret['all'],
      'count' => $ret['count']
    ]);
  }

  public function selectExamBody()
  {
    $allExamBody = $this->examHelper->getAllExamBody();

    $subs = \App\Helpers\SubscriptionHelper::getUserSubscriptionsByContentId(
      Auth::user()
    );

    return view('home.exam.select_exam_body', [
      'allExamBody' => $allExamBody,
      'active_subs' => $subs
    ]);
  }

  public function selectExamSubjects($examContentId)
  {
    $courses = $this->examHelper->getCoursesWithSessions($examContentId);

    return view('home.exam.select_exam_subject', [
      'courses' => $courses,
      'examContentId' => $examContentId
    ]);
  }

  public function registerExam(Request $request)
  {
    $post = $request->all();
    //         dDie($post);
    //         $subjects = $post['exam_subjects'];
    $hrs = (int) $post['hours'];
    $mins = (int) $post['mins'];
    $durationInSecs = (60 * $hrs + $mins) * 60;
    //         $examContentId = $post['exam_content_id'];

    $post['duration'] = $durationInSecs;

    $user = Auth::user();
    $ret = $this->examHelper->registerExam($post, $user);

    if (!$ret[SUCCESSFUL]) {
      return Redirect::back()
        ->with('error', $ret[MESSAGE])
        ->withErrors(Arr::get($ret, 'val'));
    }

    $exam = $ret['data'];

    return redirect(route('home.start-exam', $exam['exam_no']));
  }

  function startExam($examNo)
  {
    $ret = $this->examHelper->startExam($examNo, Auth::user());

    if (!$ret[SUCCESSFUL]) {
      return redirect(route('home.init-exam'))->with('error', $ret[MESSAGE]);
    }

    return view('home.dummy', [
      'examData' => $ret['data'],
      'examNo' => $examNo
    ]);
  }

  function pauseExam(Request $request)
  {
    //         dlog($request->all());
    $ret = $this->examHelper->pauseExam(
      $request->input('exam_no'),
      Auth::user()
    );

    return json_encode($ret);
  }

  function submitExam(Request $request)
  {
    //         $content = file_get_contents('php://input');
    //         http_response_code("200");
    //         dlog($content);
    //         dlog($request->all());
    //         die('djsnd');
    //         return json_encode([SUCCESSFUL => true, MESSAGE => 'Here']);
    $attempts = $request->input('attempts', []);
    $examNo = $request->input('exam_no');
    $userId = $request->input('user_id');

    $ret = $this->examHandler->attemptQuestion($attempts, $examNo, $userId);

    $ret = $this->examHelper->endExam($examNo, $userId);

    return json_encode($ret);
  }

  function viewResult($examNo)
  {
    $exam = \App\Models\Exam::where('exam_no', '=', $examNo)
      ->with(['examSubjects', 'user'])
      ->first();

    if (!$exam) {
      return redirect(route('user-dashboard'))->with('error', 'Exam not found');
    }
    if ($exam->status !== 'ended' && $exam->status !== 'expired') {
      return redirect(route('user-dashboard'))->with(
        'error',
        'Exam has not been concluded'
      );
    }

    $examSubjects = $exam['examSubjects'];

    if (empty($examSubjects) || !$examSubjects->first()) {
      return redirect(route('user-dashboard'))->with(
        'error',
        'No subjects recorded for this exam'
      );
    }

    $user = $exam->user;

    $examBody = $examSubjects
      ->first()
      ->course()
      ->first()
      ->examContent()
      ->first();

    return view('home.exam.result', [
      'exam' => $exam,
      'examSubjects' => $examSubjects,
      'user' => $user,
      'examBody' => $examBody
    ]);
  }

  function previewExamResult($examNo)
  {
    $exam = Exam::where('exam_no', '=', $examNo)
      ->with(['examSubjects', 'user'])
      ->first();

    if (!$exam) {
      return redirect(route('user-dashboard'))->with(
        'error',
        'No subjects recorded for this exam'
      );
    }

    $user = $exam['user'];
    $allAttempts = [];
    $getContent = $this->examHandler->getContent($examNo, $user->id ?? null);

    if ($getContent['success']) {
      $allAttempts = Arr::get($getContent['content'], 'attempts');
    }

    return view('home/exam/preview_exam_result', [
      'exam' => $exam,
      'examSubjects' => $exam['examSubjects'],
      'allAttempts' => $allAttempts
    ]);
  }

  /** @deprecated */
  function previewResult($session_id, $examNo = null)
  {
    $sessionDetails = \App\Models\CourseSession::where('id', '=', $session_id)
      ->with([
        'course',
        'questions',
        'instructions',
        'passages',
        'questions.topic'
      ])
      ->first();

    if (!$sessionDetails) {
      return $this->view('ccd/layout', ['error' => 'No Record found']);
    }

    $allSessionQuestions = $sessionDetails['questions'];
    // 		$allSessionQuestions = $sessionDetails->questions()->get();

    $allPassages = $sessionDetails['passages'];
    $allInstructions = $sessionDetails['instructions'];
    $course = $sessionDetails['course'];

    if (!$allSessionQuestions->first()) {
      return redirect(route('user-dashboard'))->with(
        'error',
        'No subjects recorded for this exam'
      );
    }

    //         $this->examHandler->getContent($examNo, $userId)

    return view('home/exam/preview_exam_subject_result', [
      'year' => $sessionDetails['session'],
      //             'courseId' => $courseId,
      'courseName' => $course['code'],
      'course' => $course,
      'allCourseYearQuestions' => $allSessionQuestions,
      'year_id' => $session_id,
      'session' => $sessionDetails['session'],
      'allPassages' => $allPassages,
      'allInstructions' => $allInstructions
    ]);
  }
}
