<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseTeacher extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'classification_id' => 'integer',
    'user_id' => 'integer',
    'course_id' => 'integer'
  ];

  function courseResultQuery()
  {
    return CourseResult::query()
      ->where('course_id', $this->course_id)
      ->where('teacher_user_id', $this->user_id)
      ->where('classification_id', $this->classification_id);
  }

  function otherTeacherCourses()
  {
    return CourseTeacher::query()
      ->select('course_teachers.*')
      ->join(
        'classifications',
        'course_teachers.classification_id',
        'classifications.id'
      )
      ->where('user_id', $this->user_id)
      ->with('course', 'classification')
      ->oldest('classifications.title')
      ->get()
      ->keyBy('id');
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }
}
