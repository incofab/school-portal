<?php

namespace App\Models;

use App\Rules\ValidateExistsRule;
use App\Traits\HasMedia;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonPlan extends BaseModel
{
  use HasFactory, HasMedia, InstitutionScope, SoftDeletes;

  protected $table = 'lesson_plans';

  protected $guarded = [];

  protected $casts = [
    'institution_group_id' => 'integer',
    'institution_id' => 'integer',
    'scheme_of_work_id' => 'integer',
    'teacher_user_id' => 'integer'
  ];

  public static function createRule()
  {
    return [
      'teacher_user_id' => ['required', 'exists:users,id'],
      'scheme_of_work_id' => [
        'nullable',
        new ValidateExistsRule(SchemeOfWork::class)
      ],
      'objective' => ['nullable', 'string'],
      'activities' => ['nullable', 'string'],
      'content' => ['required', 'string'],
      'is_used_by_institution_group' => ['required', 'boolean'],
      'institution_id' => ['required']
    ];
  }

  public function institutionGroup()
  {
    return $this->belongsTo(InstitutionGroup::class);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function schemeOfWork()
  {
    return $this->belongsTo(SchemeOfWork::class);
  }

  public function lessonNote()
  {
    return $this->hasOne(LessonNote::class);
  }

  public function lessonNotes()
  {
    return $this->hasMany(LessonNote::class);
  }

  public function teacher()
  {
    return $this->belongsTo(User::class, 'teacher_user_id');
  }
}
