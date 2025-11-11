<?php

namespace App\Models;

use App\Enums\InstitutionUserType;
use App\Rules\ValidateExistsRule;
use App\Rules\ValidateUniqueRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Validation\Rule;

class Classification extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'classification_group_id' => 'integer',
    'form_teacher_id' => 'integer',
    'has_equal_subjects' => 'boolean'
  ];

  static function createRule(
    ?Classification $classification = null,
    $prefix = ''
  ) {
    return [
      $prefix . 'title' => [
        'required',
        'string',
        'max:100',
        (new ValidateUniqueRule(Classification::class, 'title'))->when(
          $classification,
          fn($q) => $q->ignore($classification->id, 'id')
        )
      ],
      $prefix . 'description' => ['nullable', 'string'],
      $prefix . 'has_equal_subjects' => ['nullable', 'boolean'],
      $prefix . 'form_teacher_id' => [
        'nullable',
        'integer',
        new ValidateExistsRule(InstitutionUser::class, 'user_id', [
          'role' => InstitutionUserType::Teacher->value
        ])
      ],
      $prefix . 'classification_group_id' => [
        'required',
        new ValidateExistsRule(ClassificationGroup::class)
      ]
    ];
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classificationGroup()
  {
    return $this->belongsTo(ClassificationGroup::class);
  }

  function classDivisions()
  {
    return $this->morphToMany(
      ClassDivision::class,
      'mappable',
      'class_division_mappings'
    );
  }

  function formTeacher()
  {
    return $this->belongsTo(User::class, 'form_teacher_id');
  }

  function students()
  {
    return $this->hasMany(Student::class);
  }

  function courseResults()
  {
    return $this->hasMany(CourseResult::class);
  }

  function termResults()
  {
    return $this->hasMany(TermResult::class);
  }

  function sessionResults()
  {
    return $this->hasMany(SessionResult::class);
  }

  function timetables()
  {
    return $this->hasMany(Timetable::class);
  }

  public function assessments(): MorphToMany
  {
    return $this->morphedByMany(Assessment::class, 'classifiable');
  }
}
