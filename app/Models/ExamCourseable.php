<?php

namespace App\Models;

use App\Casts\TrimDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamCourseable extends Model
{
  use HasFactory;
  public $guarded = [];

  protected $casts = [
    'courseable_id' => 'integer',
    'course_id' => 'integer',
    'exam_id' => 'integer',
    'num_of_questions' => 'integer',
    'score' => TrimDecimal::class
  ];

  static function ruleCreate()
  {
    return [
      //             'exam_no' => ['required', 'string'],
      'num_of_questions' => ['required', 'numeric', 'min:1'],
      'course_id' => ['required'],
      'courseable_id' => ['required']
    ];
  }

  const STATUSES = ['active', 'ended'];

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

  // CourseSession | EventCourseable (QuestionCourseable)
  function courseable()
  {
    return $this->morphTo('courseable');
  }
}
