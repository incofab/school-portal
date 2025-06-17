<?php

namespace App\Models;

use App\Support\MorphMap;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeCategory extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  public $casts = [
    'institution_id' => 'integer',
    'fee_id' => 'integer',
    'feeable_id' => 'integer'
  ];

  function forClass(Classification $classification)
  {
    if ($this->feeable_type == MorphMap::key(Institution::class)) {
      return true;
    }
    if ($this->feeable_type == MorphMap::key(Classification::class)) {
      return $this->feeable_id == $classification->id;
    }
    if ($this->feeable_type == MorphMap::key(ClassificationGroup::class)) {
      return $this->feeable_id == $classification->classification_group_id;
    }
    return false;
  }

  function fee()
  {
    return $this->belongsTo(Fee::class);
  }

  // Institution | Classification | ClassificationGroup | Association
  function feeable()
  {
    return $this->morphTo('feeable');
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
