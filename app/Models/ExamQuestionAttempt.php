<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamQuestionAttempt extends BaseModel
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'exam_id' => 'integer',
    'institution_id' => 'integer',
    'questionable_id' => 'integer',
    'is_answered' => 'boolean',
    'answered_at' => 'datetime'
  ];

  public function exam()
  {
    return $this->belongsTo(Exam::class);
  }

  public function questionable()
  {
    return $this->morphTo();
  }
}
