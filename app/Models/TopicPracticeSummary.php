<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TopicPracticeSummary extends BaseModel
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'student_id' => 'integer',
    'classification_id' => 'integer',
    'course_id' => 'integer',
    'topic_id' => 'integer',
    'attempts_count' => 'integer',
    'latest_score' => 'integer',
    'latest_questions_count' => 'integer',
    'latest_percentage' => 'float',
    'best_score' => 'integer',
    'best_questions_count' => 'integer',
    'best_percentage' => 'float',
    'last_generated_at' => 'datetime',
    'last_submitted_at' => 'datetime'
  ];

  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function student()
  {
    return $this->belongsTo(Student::class);
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function topic()
  {
    return $this->belongsTo(Topic::class);
  }

  public function attempts()
  {
    return $this->hasMany(TopicPracticeAttempt::class);
  }
}
