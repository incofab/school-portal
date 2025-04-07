<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Enums\TermType;
use App\Rules\ValidateExistsRule;
use App\Support\Queries\AssignmentQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
  use HasFactory; 

  protected $table = 'assignments';

  protected $guarded = [];
  protected $casts = [
    'max_score' => 'integer',
    'expires_at' => 'datetime',
    'status' => AssignmentStatus::class,
    'institution_id' => 'integer',
    'course_id' => 'integer',
    'academic_session_id' => 'integer',
    'term' => TermType::class
  ];

  public static function query(): AssignmentQueryBuilder
  {
    return parent::query();
  }

  public function newEloquentBuilder($query)
  {
    return new AssignmentQueryBuilder($query);
  }

  function scopeNotExpired($query)
  {
    return $query->where('expires_at', '>', now());
  }

  // Define relationships
  public function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  public function institutionUser()
  {
    return $this->belongsTo(InstitutionUser::class);
  }

  public function courseTeacher()
  {
    return $this->belongsTo(CourseTeacher::class);
  }

  function assignmentSubmissions()
  {
    return $this->hasMany(AssignmentSubmission::class);
  }

  function assignmentClassifications()
  {
    return $this->hasMany(AssignmentClassification::class);
  }

  function classifications()
  {
    return $this->belongsToMany(
      Classification::class,
      'assignment_classifications'
    )->withTimestamps();
  }
}
