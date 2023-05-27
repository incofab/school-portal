<?php

namespace App\Models;

use App\Enums\UserRoleType;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
  use Notifiable, HasApiTokens, HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  protected $appends = ['full_name'];
  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = ['password', 'remember_token'];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'email_verified_at' => 'datetime'
  ];

  public static function generalRule()
  {
    $user = request('user');
    return [
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'other_names' => ['nullable', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:20'],
      'email' => [
        'required',
        'string',
        'email',
        'unique:users,email,' . $user?->id
      ],
      ...$user
        ? []
        : ['password' => ['required', 'string', 'confirmed', 'min:6']]
    ];
  }

  protected function fullName(): Attribute
  {
    return Attribute::make(
      get: fn() => "{$this->last_name} {$this->first_name} {$this->other_names}"
    );
  }

  /** Institutions created by this user */
  function createdInstitutions()
  {
    return $this->hasMany(Institution::class);
  }

  /** Institutions this user is assigned to */
  function institutions()
  {
    return $this->belongsToMany(Institution::class)->withTimestamps();
  }

  private InstitutionUser $institutionUserData;
  function currentInstitutionUser(): InstitutionUser
  {
    if ($this->institutionUserData) {
      $this->institutionUserData = $this->institutionUsers()
        ->where('institution_id', currentInstitution()->id)
        ->first();
    }
    return $this->institutionUserData;
  }

  function institutionUsers()
  {
    return $this->hasMany(InstitutionUser::class);
  }

  function courseTeachers()
  {
    return $this->hasMany(CourseTeacher::class);
  }

  function student()
  {
    return $this->hasOne(Student::class);
  }

  function hasRole(UserRoleType $role): bool
  {
    return $this->currentInstitutionUser()?->role === $role;
  }

  function isAdmin()
  {
    return $this->hasRole(UserRoleType::Admin);
  }

  function isTeacher()
  {
    return $this->hasRole(UserRoleType::Teacher);
  }

  function isStudent()
  {
    return $this->hasRole(UserRoleType::Student);
  }
}
