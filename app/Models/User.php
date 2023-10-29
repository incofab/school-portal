<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\InstitutionUserType;
use App\Enums\ManagerRole;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Validation\Rules\Enum;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
  use Notifiable, HasApiTokens, HasFactory, SoftDeletes;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  protected $appends = ['full_name', 'photo_url'];
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
    'email_verified_at' => 'datetime',
    'manager_role' => ManagerRole::class
  ];

  public static function generalRule($userId = null, $prefix = '')
  {
    return [
      $prefix . 'first_name' => ['required', 'string', 'max:255'],
      $prefix . 'last_name' => ['required', 'string', 'max:255'],
      $prefix . 'other_names' => ['nullable', 'string', 'max:255'],
      $prefix . 'phone' => ['nullable', 'string', 'max:20'],
      $prefix . 'gender' => ['nullable', new Enum(Gender::class)],
      $prefix . 'email' => [
        'required',
        'string',
        'email',
        'unique:users,email,' . $userId
      ],
      ...$userId
        ? []
        : [$prefix . 'password' => ['required', 'string', 'confirmed', 'min:6']]
    ];
  }

  protected function photoUrl(): Attribute
  {
    if (!$this->photo) {
      $encodedName = urlencode($this->getAttribute('full_name'));
      return new Attribute(
        get: fn() => "https://ui-avatars.com/api/?name={$encodedName}"
      );
    }
    return new Attribute(get: fn() => $this->photo);
  }

  protected function fullName(): Attribute
  {
    return Attribute::make(
      get: fn() => "{$this->first_name} {$this->other_names} {$this->last_name}"
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
    return $this->belongsToMany(
      Institution::class,
      'institution_users'
    )->withTimestamps();
  }

  function institutionUsers()
  {
    return $this->hasMany(InstitutionUser::class);
  }

  function institutionUser()
  {
    return $this->institutionUsers()
      ->where('institution_id', currentInstitution()->id)
      ->with('student');
  }

  function courseTeachers()
  {
    return $this->hasMany(CourseTeacher::class);
  }

  function student()
  {
    return $this->hasOne(Student::class);
  }

  function institutionStudent(): Student|null
  {
    return $this->institutionUser()
      ->with('student.classification')
      ->first()?->student;
  }

  function exams()
  {
    return $this->morphMany(Exam::class, 'examable');
  }

  function hasInstitutionRole(InstitutionUserType|array $role): bool
  {
    return $this->institutionUser()
      ->when(
        is_array($role),
        fn($q) => $q->whereIn('role', $role),
        fn($q) => $q->where('role', $role)
      )
      ->exists();
  }

  function isInstitutionAdmin()
  {
    return $this->hasInstitutionRole(InstitutionUserType::Admin);
  }

  function isInstitutionTeacher()
  {
    return $this->hasInstitutionRole(InstitutionUserType::Teacher);
  }

  function isInstitutionStudent()
  {
    return $this->hasInstitutionRole(InstitutionUserType::Student);
  }

  function isManagerAdmin()
  {
    return $this->manager_role === ManagerRole::Admin;
  }
}
