<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Student;
use Illuminate\Database\Eloquent\Collection;

class StudentMigration
{
  function __construct()
  {
  }

  public static function make()
  {
    return new self();
  }

  /**
   * @param Student[] $students
   * @param Classification $destinationClass
   */
  public function migrateStudents(
    Collection|array $students,
    Classification $destinationClass
  ) {
    foreach ($students as $key => $student) {
      $this->migrateStudent($student, $destinationClass);
    }
  }

  public function migrateStudent(
    Student $student,
    ?Classification $destinationClass = null
  ) {
    $student->fill(['classification_id' => $destinationClass?->id])->save();
    // Record this in the class movement activity
  }

  /** If $destinationClass is null, students will be moved to Alumni */
  public function migrateClass(
    Classification $sourceClass,
    ?Classification $destinationClass = null
  ) {
    $students = Student::query()
      ->where('classification_id', $sourceClass->id)
      ->with('institutionUser')
      ->get();
    if ($destinationClass) {
      $this->migrateStudents($students, $destinationClass);
    } else {
      $this->moveToAlumni($students);
    }
  }

  /** @param Student[] $students */
  public function moveToAlumni(Collection|array $students)
  {
    foreach ($students as $key => $student) {
      $this->migrateStudent($student, null);
      $student->institutionUser
        ->fill(['role' => InstitutionUserType::Alumni])
        ->save();
    }
  }
}
