<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssignmentSubmission extends BaseModel
{
  use HasFactory;

  protected $table = 'assignment_submissions';
  protected $guarded = [];
  protected $casts = [
    'assignment_id' => 'integer',
    'student_id' => 'integer'
  ];

  public function assignment()
  {
    return $this->belongsTo(Assignment::class);
  }

  public function student()
  {
    return $this->belongsTo(Student::class);
  }
}
