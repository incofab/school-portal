<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends Model
{
  use HasFactory;

  public $fillable = [
    'event_id',
    'student_id',
    'exam_no',
    'start_time',
    'duration',
    'time_remaining',
    'end_time',
    'pause_time',
    'status',
    'num_of_questions',
    'score'
  ];

  static function insert($post)
  {
    if (
      Exam::whereEvent_id($post['event_id'])
        ->whereStudent_id($post['student_id'])
        ->first()
    ) {
      return retF('Student already registered for this exam');
    }

    $post['exam_no'] = self::generateExamNo();
    $post['status'] = STATUS_ACTIVE;

    $data = static::create($post);

    if (!$data) {
      return retF('Error: Data entry failed');
    }

    return retS('Data recorded', $data);
  }

  static function generateExamNo()
  {
    $key = date('Y') . rand(10000000, 99999999);

    while (self::where('exam_no', '=', $key)->first()) {
      $key = date('Y') . rand(10000000, 99999999);
    }

    return $key;
  }

  function examSubjects()
  {
    return $this->hasMany(\App\Models\ExamSubject::class, 'exam_no', 'exam_no');
  }

  function student()
  {
    return $this->belongsTo(Student::class, 'student_id', 'student_id');
  }

  function event()
  {
    return $this->belongsTo(Event::class, 'event_id', 'id');
  }

  function user()
  {
    return $this->belongsTo(User::class, 'user_id', 'id');
  }
}
