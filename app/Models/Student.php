<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
  use HasFactory, SoftDeletes;

  public $guarded = [];

  static function generateStudentID()
  {
    $prefix = date('Y') . '-';

    $key = $prefix . rand(1000000, 9999999);

    while (Student::where('code', '=', $key)->first()) {
      $key = $prefix . rand(1000000, 9999999);
    }

    return $key;
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }

  function courseResults()
  {
    return $this->hasMany(CourseResult::class);
  }

  function termResults()
  {
    return $this->hasMany(TermResult::class);
  }
}
