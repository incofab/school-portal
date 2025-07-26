<?php

namespace App\Models;

use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstitutionUser extends Model
{
  use HasFactory, SoftDeletes, InstitutionScope;

  protected $guarded = [];
  public $table = 'institution_users';

  protected $casts = [
    'role' => InstitutionUserType::class,
    'institution_id' => 'integer',
    'user_id' => 'integer',
    'status' => InstitutionUserStatus::class
  ];

  function hasRole(InstitutionUserType $role): bool
  {
    return $this->role === $role;
  }

  function isSuspended(): bool
  {
    return $this->status === InstitutionUserStatus::Suspended;
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

  function isGuardian()
  {
    return $this->hasRole(InstitutionUserType::Guardian);
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

  function timetableCoordinators()
  {
    return $this->hasMany(TimetableCoordinator::class);
  }

  function salaries()
  {
    return $this->hasMany(Salary::class);
  }

  function payrollAdjustments()
  {
    return $this->hasMany(PayrollAdjustment::class);
  }
}
