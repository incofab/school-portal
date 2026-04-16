<?php

namespace App\Models;

use App\Enums\ExamStatus;
use App\Support\MorphMap;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  public $casts = [
    'status' => ExamStatus::class,
    'start_time' => 'datetime',
    'pause_time' => 'datetime',
    'end_time' => 'datetime',
    'event_id' => 'integer',
    'institution_id' => 'integer',
    'time_remaining' => 'float',
    'examable_id' => 'integer',
    'num_of_questions' => 'integer',
    'score' => 'float',
    'attempts' => AsArrayObject::class,
    'theory_score' => 'float',
    'theory_max_score' => 'float',
    'theory_evaluated' => 'boolean'
  ];

  public static function generateExamNo()
  {
    $key = date('Y') . rand(10000000, 99999999);

    while (self::where('exam_no', '=', $key)->first()) {
      $key = date('Y') . rand(10000000, 99999999);
    }

    return $key;
  }

  public static function scopeForExamable($query, $examable)
  {
    return $query
      ->where('examable_id', $examable->id)
      ->where('examable_type', MorphMap::key(get_class($examable)));
  }

  public function isEnded()
  {
    return $this->status === ExamStatus::Ended;
  }

  public function scorePercent()
  {
    return ($this->score /
      ($this->num_of_questions == 0 ? 1 : $this->num_of_questions)) *
      100;
  }

  public function getExamableName()
  {
    if ($this->examable instanceof User) {
      return $this->examable->full_name;
    } elseif ($this->examable instanceof Student) {
      return $this->examable->user->full_name;
    } elseif ($this->examable instanceof TokenUser) {
      return $this->examable->name;
    } else {
      return 'Unknown';
    }
  }

  public function examCourseables()
  {
    return $this->hasMany(ExamCourseable::class);
  }

  // TokenUser|User|Student|AdmissionApplication
  public function examable()
  {
    return $this->morphTo();
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function event()
  {
    return $this->belongsTo(Event::class);
  }
}
