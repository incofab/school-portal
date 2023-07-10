<?php

namespace App\Models;

use App\Enums\TermType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class CourseResult extends Model
{
  use HasFactory;

  protected $guarded = [];
  protected $casts = [
    'term' => TermType::class,
    'teacher_user_id' => 'integer',
    'course_id' => 'integer',
    'student_id' => 'integer',
    'institution_id' => 'integer',
    'for_mid_term' => 'boolean'
  ];
  public function rule()
  {
    return [
      'student_id' => ['required', Rule::exists('students', 'id')]
    ];
  }

  protected function assessmentValues(): Attribute
  {
    return Attribute::make(
      get: fn(string $value) => json_decode($value, true),
      set: fn(array|null $value) => json_encode($value)
    );
  }

  /** @return Collection<integer, Assessment> */
  function getAssessments()
  {
    return Assessment::query()
      ->forMidTerm($this->for_mid_term)
      ->forTerm($this->term)
      ->get();
  }

  public function courseTeacher()
  {
    return CourseTeacher::query()
      ->where('user_id', $this->teacher_user_id)
      ->where('course_id', $this->course_id)
      ->where('classification_id', $this->classification_id)
      ->first();
  }

  public function student()
  {
    return $this->belongsTo(Student::class)->with('user');
  }

  public function teacher()
  {
    return $this->belongsTo(User::class, 'teacher_user_id', 'id');
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
