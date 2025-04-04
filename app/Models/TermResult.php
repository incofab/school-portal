<?php
namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
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
    'academic_session_id' => 'integer',
    'classification_id' => 'integer',
    'for_mid_term' => 'boolean',
    'is_activated' => 'boolean',
    'next_term_resumption_date' => 'date',
    'average' => 'float',
    'learning_evaluation' => AsArrayObject::class
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function isPublished()
  {
    // Allow mid term results.
    // Todo: This could be a security risk. Some schools can intentionally mark their result as mid term
    if ($this->for_mid_term) {
      return true;
    }
    return boolval($this->result_publication_id);
  }

  function scopeIsPublished($query, $isPublished = true)
  {
    return $isPublished
      ? $query->whereNotNull('result_publication_id')
      : $query->whereNull('result_publication_id');
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
