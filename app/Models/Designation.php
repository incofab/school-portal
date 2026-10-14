<?php

namespace App\Models;

use App\Enums\DesignationScope;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Designation extends BaseModel
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'is_for_students' => 'boolean',
    'is_active' => 'boolean',
    'max_holders' => 'integer',
    'scope' => DesignationScope::class
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function eligibleClasses()
  {
    return $this->belongsToMany(
      Classification::class,
      'designation_classification'
    )
      ->withPivot('institution_id')
      ->withTimestamps();
  }

  public function assignments()
  {
    return $this->hasMany(DesignationAssignment::class);
  }

  public function activeAssignments()
  {
    return $this->assignments()->whereNull('ended_at');
  }
}
