<?php

namespace App\Models;

use App\Support\Queries\StudentQueryBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
  use HasFactory, SoftDeletes;

  public $guarded = [];
  protected $casts = [
    'user_id' => 'integer'
  ];

  protected $appends = ['full_code'];

  public static function query(): StudentQueryBuilder
  {
    return parent::query();
  }

  public function newEloquentBuilder($query)
  {
    return new StudentQueryBuilder($query);
  }

  static function generateStudentID()
  {
    $prefix = date('Y');

    $key = $prefix . rand(1000000, 9999999);

    while (Student::where('code', '=', $key)->first()) {
      $key = $prefix . rand(1000000, 9999999);
    }

    return $key;
  }

  protected function fullCode(): Attribute
  {
    $initials = currentInstitution()?->initials;
    $prefix = $initials ? "$initials/" : '';
    return Attribute::make(get: fn() => "{$prefix}{$this->code}");
  }

  static function stripInitials(string $studentCode)
  {
    $pos = strpos($studentCode, '/') ?? 0;

    return substr($studentCode, $pos);
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

  function sessionResults()
  {
    return $this->hasMany(SessionResult::class);
  }

  function classMovement()
  {
    return $this->hasMany(StudentClassMovement::class);
  }
}
