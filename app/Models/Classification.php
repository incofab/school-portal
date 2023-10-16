<?php
namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classification extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];
  protected $casts = [
    'institution_id' => 'integer',
    'has_equal_subjects' => 'boolean'
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classificationGroup()
  {
    return $this->belongsTo(ClassificationGroup::class);
  }

  function formTeacher()
  {
    return $this->belongsTo(User::class, 'form_teacher_id');
  }

  function students()
  {
    return $this->hasMany(Student::class);
  }

  function courseResults()
  {
    return $this->hasMany(CourseResult::class);
  }

  function termResults()
  {
    return $this->hasMany(TermResult::class);
  }

  function sessionResults()
  {
    return $this->hasMany(SessionResult::class);
  }
}