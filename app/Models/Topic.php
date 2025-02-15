<?php

namespace App\Models;

use App\Rules\ValidateExistsRule;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Topic extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;
  protected $table = 'topics';
  protected $guarded = [];

  protected $casts = [
    'institution_group_id' => 'integer',
    'institution_id' => 'integer',
    'classification_group_id' => 'integer',
    'course_id' => 'integer'
  ];

  static function ruleCreate()
  {
    //Used in CCD Route functions
    return [
      'course_id' => ['required'],
      'title' => ['required']
    ];
  }

  static function createRule()
  {
    return [
      'title' => ['required', 'string'],
      'description' => ['required', 'string'],
      'classification_group_id' => [
        'required',
        new ValidateExistsRule(ClassificationGroup::class)
      ],
      'course_id' => ['required', new ValidateExistsRule(Course::class)],
      'parent_topic_id' => ['nullable', new ValidateExistsRule(Topic::class)],
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

  public function classificationGroup()
  {
    return $this->belongsTo(ClassificationGroup::class);
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function schemeOfWorks()
  {
    return $this->hasMany(SchemeOfWork::class);
  }
}
