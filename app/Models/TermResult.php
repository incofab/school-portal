<?php
namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
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
    'for_mid_term' => 'boolean'
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function scopeActivated($query, $isActivated = true)
  {
    return $query->where('is_activated', $isActivated);
  }

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
