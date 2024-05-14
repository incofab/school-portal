<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class GuardianStudent extends Authenticatable
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];
  protected $casts = [
    'student_id' => 'integer',
    'guardian_user_id' => 'integer',
    'institution_id' => 'integer'
  ];

  static function isGuardianOfStudent($guadianUserId, $studentId)
  {
    return GuardianStudent::query()
      ->where('guardian_user_id', $guadianUserId)
      ->where('student_id', $studentId)
      ->exists();
  }

  function student()
  {
    return $this->belongsTo(Student::class);
  }

  function guardian()
  {
    return $this->belongsTo(User::class, 'guardian_user_id', 'id');
  }
}
