<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
