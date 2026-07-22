<?php

namespace App\Models;

use App\Rules\ValidateUniqueRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends BaseModel
{
  use HasFactory, InstitutionScope, SoftDeletes;

  protected $casts = [
    'institution_id' => 'integer',
    'order' => 'integer'
  ];

  protected $fillable = [
    'code',
    'order',
    'category',
    'title',
    'description',
    'is_file_content_uploaded',
    'institution_id'
  ];

  public static function createRule(?Course $course = null, $prefix = '')
  {
    return [
      $prefix . 'title' => [
        'required',
        (new ValidateUniqueRule(Course::class, 'title'))->when(
          $course,
          fn($q) => $q->ignore($course->id, 'id')
        )
      ],
      $prefix . 'code' => [
        'required',
        (new ValidateUniqueRule(Course::class, 'code'))->when(
          $course,
          fn($q) => $q->ignore($course->id, 'id')
        )
      ],
      $prefix . 'institution_id' => ['nullable'],
      $prefix . 'order' => ['sometimes', 'integer'],
      $prefix . 'category' => ['nullable', 'string'],
      $prefix . 'description' => ['nullable', 'string']
    ];
  }

  public function scopeOrderedByCourseOrder(Builder $query): Builder
  {
    return $query->orderBy('courses.order')->orderBy('courses.title');
  }

  public function canDelete()
  {
    return !$this->hasExistingReferences();
  }

  public function hasExistingReferences(): bool
  {
    return $this->courseSessions()
      ->withTrashed()
      ->exists() ||
      $this->topics()
        ->withTrashed()
        ->exists() ||
      $this->summaryChapters()->exists() ||
      //   $this->courseTeachers()
      //       ->withTrashed()
      //       ->exists() ||
      $this->courseResults()->exists() ||
      $this->courseResultInfo()->exists() ||
      $this->assignments()->exists() ||
      $this->lessonNotes()
        ->withTrashed()
        ->exists() ||
      $this->libraries()->exists();
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  /**
   * @deprecated use courseSessions()
   */
  public function sessions()
  {
    return $this->hasMany(CourseSession::class);
  }

  public function courseSessions()
  {
    return $this->hasMany(CourseSession::class);
  }

  public function topics()
  {
    return $this->hasMany(Topic::class);
  }

  public function summaryChapters()
  {
    return $this->hasMany(Summary::class);
  }

  public function courseTeachers()
  {
    return $this->hasMany(CourseTeacher::class);
  }

  public function courseResults()
  {
    return $this->hasMany(CourseResult::class);
  }

  public function courseResultInfo()
  {
    return $this->hasMany(CourseResultInfo::class);
  }

  public function assignments()
  {
    return $this->hasMany(Assignment::class);
  }

  public function lessonNotes()
  {
    return $this->hasMany(LessonNote::class);
  }

  public function topicPracticeSummaries()
  {
    return $this->hasMany(TopicPracticeSummary::class);
  }

  public function topicPracticeAttempts()
  {
    return $this->hasMany(TopicPracticeAttempt::class);
  }

  public function libraries()
  {
    return $this->hasMany(Library::class);
  }
}
