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
    'theory_num_of_questions' => 'integer',
    'score' => TrimDecimal::class,
    'theory_score' => TrimDecimal::class,
    'theory_max_score' => TrimDecimal::class,
    'theory_question_scores' => 'array',
    'theory_evaluated' => 'boolean'
  ];

  public static function ruleCreate()
  {
    return [
      //             'exam_no' => ['required', 'string'],
      'num_of_questions' => ['required', 'numeric', 'min:1'],
      'course_id' => ['required'],
      'courseable_id' => ['required']
    ];
  }

  const STATUSES = ['active', 'ended'];

  public function scorePercent()
  {
    return ($this->score /
      ($this->num_of_questions == 0 ? 1 : $this->num_of_questions)) *
      100;
  }

  public function totalNumOfQuestions()
  {
    return $this->num_of_questions + $this->theory_num_of_questions;
  }

  public function hasTheoryQuestions()
  {
    return $this->theory_num_of_questions > 0;
  }

  public function exam()
  {
    return $this->belongsTo(Exam::class);
  }

  // CourseSession | EventCourseable (QuestionCourseable)
  public function courseable()
  {
    return $this->morphTo('courseable');
  }
}
