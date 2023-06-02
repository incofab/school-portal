<?php

namespace App\Models;

use App\Enums\InstitutionUserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionUser extends Model
{
  use HasFactory;
  protected $guarded = [];
  public $table = 'institution_users';

  protected $casts = [
    'role' => InstitutionUserType::class
  ];

  function hasRole(InstitutionUserType $role): bool
  {
    return $this->role === $role;
  }

  function isAdmin()
  {
    return $this->hasRole(InstitutionUserType::Admin);
  }

  function isTeacher()
  {
    return $this->hasRole(InstitutionUserType::Teacher);
  }

  function isStudent()
  {
    return $this->hasRole(InstitutionUserType::Student);
  }

  function isStaff()
  {
    return $this->hasRole(InstitutionUserType::Teacher) ||
      $this->hasRole(InstitutionUserType::Admin);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function student()
  {
    return $this->hasOne(Student::class);
  }

  function user()
  {
    return $this->belongsTo(User::class);
  }
}
