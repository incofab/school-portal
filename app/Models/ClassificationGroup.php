<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassificationGroup extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer'
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
