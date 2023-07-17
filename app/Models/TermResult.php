<?php
namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermResult extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  protected $casts = [
    'term' => TermType::class,
    'teacher_user_id' => 'integer',
    'student_id' => 'integer',
    'institution_id' => 'integer',
    'for_mid_term' => 'boolean',
    'learning_evaluation' => AsArrayObject::class
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function scopeActivated($query, $isActivated = true)
  {
    return $query->where('is_activated', $isActivated);
  }

  // protected function learningEvaluation(): Attribute
  // {
  //   return Attribute::make(
  //     get: fn($value) => json_decode($value, true),
  //     set: fn($value) => json_encode($value, JSON_PRETTY_PRINT)
  //   );
  // }

  function student()
  {
    return $this->belongsTo(Student::class);
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
