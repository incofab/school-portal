<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAssociation extends Model
{
  use HasFactory, InstitutionScope, SoftDeletes;

  public $guarded = [];
  public $casts = [
    'institution_id' => 'integer',
    'institution_user_id' => 'integer',
    'association_id' => 'integer'
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
  function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class);
  }
  function association()
  {
    return $this->belongsTo(Association::class);
  }
}
