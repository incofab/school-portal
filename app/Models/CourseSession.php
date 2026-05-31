<?php

namespace App\Models;

use App\Models\Support\QuestionCourseable;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseSession extends QuestionCourseable
{
  use HasFactory, InstitutionScope, SoftDeletes;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'course_id' => 'integer'
  ];

  public static function createRule($editUser = null)
  {
    return [
      'session' => ['required', 'string'],
      'category' => ['nullable', 'string'],
      'general_instructions' => ['nullable', 'string']
    ];
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function getName()
  {
    return "{$this->course->title} {$this->session}";
  }

  public function hasExistingReferences(): bool
  {
    return $this->questions()->exists() ||
      $this->theoryQuestions()->exists() ||
      $this->instructions()->exists() ||
      $this->passages()->exists() ||
      $this->eventCourseables()->exists() ||
      $this->examCourseables()->exists() ||
      $this->media()->exists();
  }

  public function eventCourseables()
  {
    return $this->morphMany(EventCourseable::class, 'courseable');
  }

  public function examCourseables()
  {
    return $this->morphMany(ExamCourseable::class, 'courseable');
  }
}
