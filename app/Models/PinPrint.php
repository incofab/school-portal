<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @deprecated No longer in use */
class PinPrint extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  function pins()
  {
    return $this->hasMany(Pin::class);
  }
  function user()
  {
    return $this->belongsTo(User::class);
  }
  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
