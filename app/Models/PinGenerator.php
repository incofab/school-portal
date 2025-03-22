<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinGenerator extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  public $casts = [
    'institution_id' => 'integer',
    'user_id' => 'integer'
  ];

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
