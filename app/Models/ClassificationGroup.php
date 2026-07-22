<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassificationGroup extends BaseModel
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'show_class_group_position' => 'boolean'
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classifications()
  {
    return $this->hasMany(Classification::class);
  }
}
