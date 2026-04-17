<?php

namespace App\Models;

use App\Enums\TermType;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseResultInfo extends Model
{
  use HasFactory, InstitutionScope;
  public $table = 'course_result_info';

  protected $guarded = [];
  protected $casts = [
    'term' => TermType::class,
    'course_id' => 'integer',
    'institution_id' => 'integer',
    'classification_id' => 'integer',
    'academic_session_id' => 'integer',
    'for_mid_term' => 'boolean'
  ];

  function courseResultQuery()
  {
    return CourseResult::query()
      ->where('course_id', $this->course_id)
      ->where('classification_id', $this->classification_id)
      ->where('academic_session_id', $this->academic_session_id)
      ->where('term', $this->term)
      ->where('for_mid_term', $this->for_mid_term);
  }

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
