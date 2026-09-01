<?php

namespace App\Models;

use App\Support\MorphMap;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeeCategory extends BaseModel
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  public $casts = [
    'institution_id' => 'integer',
    'fee_id' => 'integer',
    'feeable_id' => 'integer'
  ];

  public function forClass(?Classification $classification)
  {
    if ($this->feeable_type == MorphMap::key(Institution::class)) {
      return true;
    }
    if (is_null($classification)) {
      return false;
    }
    if ($this->feeable_type == MorphMap::key(Classification::class)) {
      return $this->feeable_id == $classification->id;
    }
    if ($this->feeable_type == MorphMap::key(ClassificationGroup::class)) {
      return $this->feeable_id == $classification->classification_group_id;
    }

    return false;
  }

  public function forStudent(Student $student): bool
  {
    return $this->feeable_type == MorphMap::key(Student::class) &&
      $this->feeable_id == $student->id;
  }

  public function fee()
  {
    return $this->belongsTo(Fee::class);
  }

  /**
   * Eager loads the `user` relation on the feeable when it resolves to
   * a Student, so student-targeted fee categories can be labelled.
   */
  public static function feeableConstraint(): \Closure
  {
    return function ($morphTo) {
      $morphTo->morphWith([
        Student::class => ['user']
      ]);
    };
  }

  // Institution | Classification | ClassificationGroup | Association | Student
  public function feeable()
  {
    return $this->morphTo('feeable');
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
