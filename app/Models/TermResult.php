<?php
namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use URL;

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

  //https://edumanager.ng/9f3f2dd1-400d-4e7f-8d2d-ba1a30a5f6d9/students/signed-result-sheet/10950/864/5/first/false?expires=1765312485&signature=e458484818440bf0129fa353c54df9b9f640128a67fe956525e58fb42ce1fdaa
  function signedUrl()
  {
    return URL::temporarySignedRoute(
      'institutions.students.result-sheet.signed',
      now()->addHour(),
      [
        $this->institution->uuid,
        $this->student_id,
        $this->classification_id,
        $this->academic_session_id,
        $this->term->value,
        $this->for_mid_term ?? false
      ]
    );
  }

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
    return $query->where(
      fn($q) => $q
        ->where('is_activated', $isActivated)
        ->orWhere('for_mid_term', true)
    );
  }

  function isActivated()
  {
    return $this->is_activated || $this->for_mid_term;
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

  function resultPublication()
  {
    return $this->belongsTo(ResultPublication::class);
  }
}
