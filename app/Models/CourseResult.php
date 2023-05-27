<?php

namespace App\Models;

use App\Enums\Term;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class CourseResult extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = ['term' => Term::class];
  public function rule()
  {
    return [
      'student_id' => ['required', Rule::exists('students', 'id')]
    ];
  }
  public function student()
  {
    return $this->belongsTo(Student::class);
  }

  public function teacher()
  {
    return $this->belongsTo(User::class, 'teacher_user_id', 'id');
  }

  public function academicSession()
  {
    return $this->belongsTo(AcademicSession::class);
  }
}
