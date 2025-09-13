<?php
namespace App\Models;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionResult extends Model
{
  use HasFactory, InstitutionScope;

  public $guarded = [];

  protected $casts = [
    'student_id' => 'integer',
    'classification_id' => 'integer',
    'academic_session_id' => 'integer',
    'institution_id' => 'integer',
    'position' => 'integer'
  ];

  function institution()
  {
    return $this->belongsTo(Institution::class);
  }

  function classification()
  {
    return $this->belongsTo(Classification::class);
  }

  function student()
  {
    return $this->belongsTo(Student::class);
  }

  function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
