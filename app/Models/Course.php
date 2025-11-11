<?php

namespace App\Models;

use App\Rules\ValidateUniqueRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
  use HasFactory, InstitutionScope;

  protected $casts = [
    'institution_id' => 'integer'
  ];
  protected $fillable = [
    'code',
    'category',
    'title',
    'description',
    'is_file_content_uploaded',
    'institution_id'
  ];

  static function createRule(?Course $course = null, $prefix = '')
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
      $prefix . 'category' => ['nullable', 'string'],
      $prefix . 'description' => ['nullable', 'string']
    ];
  }

  public function canDelete()
  {
    return $this->sessions()
      ->get()
      ->count() === 0 &&
      $this->topics()
        ->get()
        ->count() === 0 &&
      $this->summaryChapters()
        ->get()
        ->count() === 0;
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function sessions()
  {
    return $this->hasMany(CourseSession::class);
  }

  function topics()
  {
    return $this->hasMany(Topic::class);
  }

  function summaryChapters()
  {
    return $this->hasMany(Summary::class);
  }

  function courseTeachers()
  {
    return $this->hasMany(CourseTeacher::class);
  }
}
