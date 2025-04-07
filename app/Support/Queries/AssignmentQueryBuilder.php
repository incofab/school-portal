<?php

namespace App\Support\Queries;

use App\Enums\TermType;
use App\Models\InstitutionUser;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class AssignmentQueryBuilder extends Builder
{
  function init() {
    $this->select('assignments.*')->join(
      'assignment_classifications',
      'assignments.id',
      'assignment_classifications.assignment_id'
    );
    return $this;
  }

  public function forStudent(Student $student): self
  {
    $this->where('assignment_classifications.classification_id', $student->classification_id);
    return $this;
  }

  public function forTeacher(InstitutionUser $institutionUser): self
  {
    $this->join('course_teachers', 'course_teachers.classification_id', 'assignment_classifications.classification_id')
    ->where('course_teachers.user_id', $institutionUser->user_id);
    return $this;
  }
}
