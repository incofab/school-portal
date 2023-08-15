<?php
namespace App\Helpers;

use App\Models\Event;
use App\Models\Exam;

class EventHelper
{
  function getEventResult($event_id, $institutionId = null)
  {
    /** @var \App\Models\Event $event */
    $event = Event::where('id', '=', $event_id);

    if ($institutionId) {
      $event = $event->where('institution_id', '=', $institutionId);
    }

    $event = $event->with(['eventSubjects', 'eventSubjects.course'])->first();

    if (!$event) {
      return retF('Event not found');
    }

    $exams = Exam::where('event_id', '=', $event->id);

    $exams = $exams
      ->with(['student', 'examSubjects', 'examSubjects.course'])
      ->get();

    $resultList = [];
    foreach ($exams as $examData) {
      $student = $examData['student'];

      /** @var \App\Models\ExamSubject $examSubject */
      $examSubjects = $examData['examSubjects'];

      $subjectsAndScoresArr = [];
      $subjectsCourseCode = [];
      $scorePercent = 0;
      foreach ($examSubjects as $examSubject) {
        $course = $examSubject['course'];

        $subjectPercentScore = \App\Core\Settings::getPercentage(
          $examSubject['score'],
          $examSubject['num_of_questions'],
          0
        );

        $subjectsCourseCode[] =
          $course['course_code'] . "=$subjectPercentScore";

        $scorePercent += $subjectPercentScore;

        $subjectsAndScoresArr[$course['course_code']] = [
          'score' => $examSubject['score'],
          'total' => $examSubject['num_of_questions']
        ];
      }

      $resultList[] = [
        'firstname' => $student['firstname'],

        'lastname' => $student['lastname'],

        'name' => $student['firstname'] . ' ' . $student['lastname'],

        'student_id' => $student['student_id'],

        'phone' => $student['phone'],

        'exam_no' => $examData['exam_no'],

        'subjects' => implode(', ', $subjectsCourseCode),

        'total_score' => $examData['score'],

        'total_score_percent' => $scorePercent,

        'total_num_of_questions' => $examData['num_of_questions'],

        'total_num_of_questions_percent' => $examSubjects->count() * 100,

        'subjects_and_scores' => $subjectsAndScoresArr
      ];
    }

    return [
      SUCCESSFUL => true,
      MESSAGE => '',
      'result_list' => $resultList,
      'event' => $event
    ];
  }

  function smsResult(
    $eventId,
    $username,
    $password,
    $centerCode,
    $redirect = null
  ) {
    $ret = $this->getEventResult($eventId, $centerCode, $redirect);

    if (!$ret[SUCCESSFUL]) {
      return $ret;
    }

    $resultList = $ret['result_list'];

    $event = $ret['event']->toArray();

    //         MESSAGE => "Your {$event[TITLE]} score is {$result['subjects']}"
    //             ." - Total={$result['total_score_percent']}/100",

    $url = DEV
      ? 'http://localhost/formula1/mock/send-result-sms'
      : 'http://formula1autozone.com.ng/mock/send-result-sms';

    $data = [
      'result_list' => json_encode($resultList),

      'event' => json_encode($event),

      'password' => $password,

      'username' => $username,

      'sys_id' => '' //$this->sysID->getId(),
    ];

    return $this->executeCurl($url, $data);
  }

  function smsInvite(
    Event $event,
    $username,
    $password,
    $time,
    $redirect = null
  ) {
    $exams = $event
      ->exams()
      ->with(['student'])
      ->get(['id', 'exam_no', 'student_id']);

    $event = $event->toArray();

    $url = DEV
      ? 'http://localhost/formula1/mock/send-sms-invite'
      : 'http://formula1autozone.com.ng/mock/send-sms-invite';

    $data = [
      'event' => json_encode($event),

      'exams' => json_encode($exams),

      'password' => $password,

      'username' => $username,

      'time' => $time,

      'sys_id' => '' //$this->sysID->getId(),
    ];

    return $this->executeCurl($url, $data);
  }

  private function executeCurl($url, $data)
  {
    // PHP cURL  for https connection with auth
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    // converting
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
      return [SUCCESSFUL => false, MESSAGE => 'Error connecting to server'];
    }

    return json_decode($response, true);
  }

  function pauseAllExam($eventId)
  {
    $event = Event::where('id', '=', $eventId)->first();

    if (!$event) {
      return [SUCCESSFUL => false, MESSAGE => 'Event Not found'];
    }

    $exams = $event->exams()->get();

    $num = 0;
    foreach ($exams as $exam) {
      $ret = $exam->pauseSelectedExam($exam);

      if ($ret[SUCCESSFUL]) {
        $num++;
      }
    }

    return [SUCCESSFUL => false, MESSAGE => "$num student(s) exam paused"];
  }

  function list(
    $status = 'all',
    $institutionId = null,
    $num = 100,
    $page = 1,
    $lastIndex = 0
  ) {
    $allRecords = Event::with(['eventSubjects']);

    if ($institutionId) {
      $allRecords = $allRecords->where('institution_id', '=', $institutionId);
    }

    if ($status && $status !== 'all') {
      $allRecords = $allRecords->where('status', '=', $status);
    }

    if ($lastIndex != 0) {
      $allRecords = $allRecords->where('id', '<', $lastIndex);
    } else {
      $allRecords = $allRecords->skip($num * ($page - 1));
    }

    $allRecords = $allRecords
      ->orderBy('id', 'DESC')
      ->skip($num * ($page - 1))
      ->take($num)
      ->get();

    $count = 1000;

    return [
      SUCCESSFUL => true,
      MESSAGE => '',
      'result' => $allRecords,
      'count' => $count
    ];
  }
}
