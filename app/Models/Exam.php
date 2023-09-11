<?php

namespace App\Models;

use App\Enums\ExamStatus;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  public $casts = [
    'status' => ExamStatus::class,
    'start_time' => 'datetime',
    'pause_time' => 'datetime',
    'end_time' => 'datetime',
    'attempts' => AsArrayObject::class
  ];

  static function generateExamNo()
  {
    $key = date('Y') . rand(10000000, 99999999);

    while (self::where('exam_no', '=', $key)->first()) {
      $key = date('Y') . rand(10000000, 99999999);
    }

    return $key;
  }

  function examCourseables()
  {
    return $this->hasMany(ExamCourseable::class);
  }

  // TokenUser|User
  function examable()
  {
    return $this->morphTo();
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function event()
  {
    return $this->belongsTo(Event::class);
  }
}
