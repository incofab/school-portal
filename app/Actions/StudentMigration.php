<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class StudentMigration
{
  function __construct(private User $staffUser)
  {
  }

  public static function make(User $staffUser)
  {
    return new self($staffUser);
  }

  /**
   * @param Student[] $students
   * @param Classification $destinationClass
   */
  public function migrateStudents(
    Collection|array $students,
    Classification $destinationClass
  ) {
    $batchNo = uniqid();
    foreach ($students as $key => $student) {
      $this->migrateStudent($student, $destinationClass, $batchNo);
    }
  }

  public function migrateStudent(
    Student $student,
    ?Classification $destinationClass = null,
    ?string $batchNo = null
  ) {
    $sourceClassId = $student->classification_id;
    $student->fill(['classification_id' => $destinationClass?->id])->save();
    // Record this in the class movement activity
    $student->classMovement()->create([
      'institution_id' => $student->institutionUser->institution_id,
      'source_classification_id' => $sourceClassId,
      'destination_classification_id' => $destinationClass?->id,
      'user_id' => $this->staffUser->id,
      'batch_no' => $batchNo ?? uniqid()
    ]);
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
    $batchNo = uniqid();
    foreach ($students as $key => $student) {
      $this->migrateStudent($student, null, $batchNo);
      $student->institutionUser
        ->fill(['role' => InstitutionUserType::Alumni])
        ->save();
    }
  }
}
