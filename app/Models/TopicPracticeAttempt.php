<?php

namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TopicPracticeAttempt extends BaseModel
{
  use HasFactory, InstitutionScope;

  protected $guarded = [];

  protected $casts = [
    'topic_practice_summary_id' => 'integer',
    'institution_id' => 'integer',
    'student_id' => 'integer',
    'classification_id' => 'integer',
    'course_id' => 'integer',
    'topic_id' => 'integer',
    'attempt_number' => 'integer',
    'questions' => 'array',
    'answers' => 'array',
    'score' => 'integer',
    'questions_count' => 'integer',
    'answered_questions_count' => 'integer',
    'percentage' => 'float',
    'submitted_at' => 'datetime'
  ];

  public function summary()
  {
    return $this->belongsTo(
      TopicPracticeSummary::class,
      'topic_practice_summary_id'
    );
  }

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
}
