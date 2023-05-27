<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
  use HasFactory, HasUuids;

  protected $fillable = [
    'uuid',
    'code',
    'name',
    'address',
    'phone',
    'email',
    'status'
  ];

  public function getRouteKeyName()
  {
    return 'uuid';
  }

  static function insert($post)
  {
    $post['code'] = static::generateInstitutionCode();

    $data = static::create($post);

    if (!$data) {
      return retF('Error: Data entry failed');
    }

    return retS('Registration successful, You can login now', $data);
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
    return $this->hasMany(Course::class, 'course_id', 'id');
  }

  function classifications()
  {
    return $this->hasMany(Classification::class);
  }

  function users()
  {
    return $this->belongsToMany(User::class);
  }

  function createdBy()
  {
    return $this->belongsTo(User::class);
  }
}
