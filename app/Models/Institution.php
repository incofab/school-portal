<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
  use HasFactory;

  protected $guarded = [];
  public function getRouteKeyName()
  {
    return 'uuid';
  }

  public function resolveRouteBinding($value, $field = null)
  {
    $institutionModel = Institution::query()
      ->select('institutions.*')
      ->join(
        'institution_users',
        'institution_users.institution_id',
        'institutions.id'
      )
      ->where('uuid', $value)
      ->where('institution_users.user_id', currentUser()->id)
      ->with('institutionUsers')
      ->firstOrFail();
    return $institutionModel;
  }

  static function generateInstitutionCode()
  {
    $key = mt_rand(100000, 999999);

    while (Institution::whereCode($key)->first()) {
      $key = mt_rand(100000, 999999);
    }

    return $key;
  }

  function courses()
  {
    return $this->hasMany(Course::class);
  }

  function classifications()
  {
    return $this->hasMany(Classification::class);
  }

  function users()
  {
    return $this->belongsToMany(User::class);
  }

  function institutionUsers()
  {
    return $this->hasMany(InstitutionUser::class);
  }

  function createdBy()
  {
    return $this->belongsTo(User::class);
  }
}
