<?php

namespace App\Models;

use App\Models\Support\QuestionCourseable;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseSession extends QuestionCourseable
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'course_id' => 'integer'
  ];

  static function createRule($editUser = null)
  {
    return [
      'session' => ['required', 'string'],
      'category' => ['nullable', 'string'],
      'general_instructions' => ['nullable', 'string']
    ];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function course()
  {
    return $this->belongsTo(Course::class);
  }

  function getName()
  {
    return "{$this->course->title} {$this->session}";
  }
}
