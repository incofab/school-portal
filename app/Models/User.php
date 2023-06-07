<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\InstitutionUserType;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Validation\Rules\Enum;
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
    'email_verified_at' => 'datetime'
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
    return $this->belongsToMany(
      Institution::class,
      'institution_users'
    )->withTimestamps();
  }

  // private InstitutionUser $institutionUserData;
  // function currentInstitutionUser(): InstitutionUser
  // {
  //   if ($this->institutionUserData) {
  //     $this->institutionUserData = $this->institutionUsers()
  //       ->where('institution_id', currentInstitution()->id)
  //       ->first();
  //   }
  //   return $this->institutionUserData;
  // }

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
    // return Student::query()
    //   ->join(
    //     'classifications',
    //     'classifications.id',
    //     '=',
    //     'students.classification_id'
    //   )
    //   ->where('classifications.institution_id', currentInstitution()->id)
    //   ->where('students.user_id', $this->id)
    //   ->first();
  }

  function hasInstitutionRole(InstitutionUserType $role): bool
  {
    return $this->institutionUser()
      ->where('role', $role)
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
}
