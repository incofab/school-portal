<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
  use HasFactory, InstitutionScope;

  protected $table = 'staff';
  public $guarded = [];
  protected $casts = [
    'institution_user_id' => 'integer',
    'institution_id' => 'integer'
  ];

  static function generateID()
  {
    $prefix = 'T' . date('Y');
    $key = $prefix . rand(1000000, 9999999);
    while (static::where('code', '=', $key)->first()) {
      $key = $prefix . rand(1000000, 9999999);
    }
    return $key;
  }

  function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }
}
