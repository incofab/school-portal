<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Student;

class UpdateStudentClass
{
  function __construct(private Student $student)
  {
  }

  public static function make(Student $student)
  {
    return new self($student);
  }

  public function changeClass(Classification $destinationClass)
  {
    $this->student
      ->fill(['classification_id' => $destinationClass->id])
      ->save();

    // Record this in the class movement activity
  }

  public function moveToAlumni()
  {
    $this->student->fill(['classification_id' => null])->save();

    $this->student
      ->institutionUser()
      ->getQuery()
      ->update(['institution_users.role' => InstitutionUserType::Alumni]);

    // Record this in the class movement activity
  }
}
