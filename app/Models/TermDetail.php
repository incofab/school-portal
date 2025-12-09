<?php
namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermDetail extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  protected $casts = [
    'term' => TermType::class,
    'institution_id' => 'integer',
    'academic_session_id' => 'integer',
    'for_mid_term' => 'boolean',
    'expected_attendance_count' => 'integer',
    'start_date' => 'date',
    'end_date' => 'date',
    'next_term_resumption_date' => 'date',
    'is_activated' => 'boolean'
  ];

  function scopeForTermResult($query, TermResult $termResult)
  {
    return $query->where([
      'academic_session_id' => $termResult->academic_session_id,
      'term' => $termResult->term,
      'for_mid_term' => $termResult->for_mid_term
    ]);
  }

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
