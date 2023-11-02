<?php
namespace App\Actions;

use App\Enums\InstitutionUserType;
use App\Models\Classification;
use App\Models\Student;
use App\Models\StudentClassMovement;
use App\Models\User;
use App\Support\SettingsHandler;
use Illuminate\Database\Eloquent\Collection;

class StudentMigration
{
  private SettingsHandler $settingsHandler;
  function __construct(private User $staffUser)
  {
    $this->settingsHandler = SettingsHandler::makeFromRoute();
  }

  public static function make(User $staffUser)
  {
    return new self($staffUser);
  }

  function generateBatchNo(): string
  {
    return uniqid();
  }

  public function migrateStudent(
    Student $student,
    ?Classification $sourceClass = null,
    ?Classification $destinationClass = null,
    ?string $batchNo = null,
    array $extras = []
  ) {
    abort_if(
      empty($sourceClass) && empty($destinationClass),
      403,
      'Source and destination class not supplied'
    );

    $student->fill(['classification_id' => $destinationClass?->id])->save();

    // If there's no $destinationClass, It mean's student is being moved to Alumni
    if (!$destinationClass) {
      $student->institutionUser
        ->fill(['role' => InstitutionUserType::Alumni])
        ->save();
    }

    // If there's no $sourceClass, It mean's an alumni is being moved back to student
    if (!$sourceClass) {
      $student->institutionUser
        ->fill(['role' => InstitutionUserType::Student])
        ->save();
    }

    $student->classMovement()->create([
      'institution_id' => $student->institutionUser->institution_id,
      'source_classification_id' => $sourceClass?->id,
      'destination_classification_id' => $destinationClass?->id,
      'user_id' => $this->staffUser->id,
      'batch_no' => $batchNo ?? uniqid(),
      'academic_session_id' => $this->settingsHandler->getCurrentAcademicSession(),
      'term' => $this->settingsHandler->getCurrentTerm(),
      ...$extras
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
    $batchNo = uniqid();
    /** @var Student $student */
    foreach ($students as $key => $student) {
      $this->migrateStudent(
        $student,
        $student->classification,
        $destinationClass,
        $batchNo
      );
    }
  }

  /**
   * @param StudentClassMovement[] $studentClassMovement
   *
   */
  public function revertStudentClassMovements(
    Collection|array $studentClassMovements
  ) {
    $batchNo = $batchNo ?? uniqid();
    /** @var StudentClassMovement $studentClassMovement */
    foreach ($studentClassMovements as $key => $studentClassMovement) {
      $student = $studentClassMovement->student;
      $sourceClass = $studentClassMovement->destinationClass;
      $destinationClass = $studentClassMovement->sourceClass;
      $this->migrateStudent(
        $student,
        $sourceClass,
        $destinationClass,
        $batchNo,
        ['revert_reference_id' => $studentClassMovement->id]
      );
    }
  }
}
