<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamCourseable extends Model
{
  use HasFactory;
  public $guarded = [];

  static function ruleCreate()
  {
    return [
      //             'exam_no' => ['required', 'string'],
      'num_of_questions' => ['required', 'numeric', 'min:1'],
      'course_id' => ['required'],
      'course_session_id' => ['required']
    ];
  }

  const STATUSES = ['active', 'ended'];

  // function insert($postvalidatedPostData)
  // {
  //   $arr = [];
  //   $arr['exam_no'] = $postvalidatedPostData['exam_no'];
  //   $arr['course_id'] = $postvalidatedPostData['course_id'];
  //   $arr['course_session_id'] = $postvalidatedPostData['course_session_id'];
  //   $arr['status'] = 'active';

  //   $data = $this->create($arr);

  //   if ($data) {
  //     return retF('Error: Data entry failed');
  //   }

  //   return retS('Data recorded', $data->toArray());
  // }

  // static function multiSubjectInsert($selectedSessionIDs, Exam $exam)
  // {
  //   foreach ($selectedSessionIDs as $courseSessionId) {
  //     $courseSessionId = trim($courseSessionId);

  //     $eventSubject = EventSubject::where(function ($query) use (
  //       $exam,
  //       $courseSessionId
  //     ) {
  //       $query
  //         ->where('event_id', '=', $exam['event_id'])
  //         ->where('course_session_id', '=', $courseSessionId);
  //     })->first();

  //     if (!$eventSubject) {
  //       continue;
  //     }

  //     $arr = [];
  //     $arr['exam_no'] = $exam['exam_no'];
  //     $arr['course_id'] = $eventSubject['course_id'];
  //     $arr['course_session_id'] = $eventSubject['course_session_id'];
  //     $arr['status'] = 'active';

  //     static::create($arr);
  //   }

  //   return successRes('Data recorded');
  // }

  function scorePercent()
  {
    return ($this->score /
      ($this->num_of_questions == 0 ? 1 : $this->num_of_questions)) *
      100;
  }

  function exam()
  {
    return $this->belongsTo(Exam::class);
  }

  // CourseSession | CourseTerm
  function courseable()
  {
    return $this->morphTo('courseable');
  }
}
