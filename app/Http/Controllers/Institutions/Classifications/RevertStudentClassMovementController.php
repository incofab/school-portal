<?php

namespace App\Http\Controllers\Institutions\Classifications;

use App\Actions\StudentMigration;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\StudentClassMovement;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RevertStudentClassMovementController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function revertSingleStudentClassMovement(
    Request $request,
    Institution $institution,
    StudentClassMovement $studentClassMovement
  ) {
    $studentClassMovement->load(['destinationClass', 'sourceClass', 'student']);
    StudentMigration::make(currentUser())->revertStudentClassMovements([
      $studentClassMovement
    ]);

    return $this->ok();
  }

  public function revertBatchStudentClassMovement(
    Request $request,
    Institution $institution
  ) {
    $data = $request->validate([
      'batch_no' => ['required'],
      'change_class' => ['nullable', 'boolean'],
      'destination_classification_id' => [
        'nullable',
        'integer',
        Rule::requiredIf($request->change_class && !$request->move_to_alumni)
      ],
      'move_to_alumni' => ['nullable', 'boolean']
    ]);

    $studentClassMovements = StudentClassMovement::where(
      'batch_no',
      $data['batch_no']
    )
      ->with(['destinationClass', 'sourceClass', 'student.classification'])
      ->get();

    if ($request->change_class) {
      $destinationClass = $request->move_to_alumni
        ? null
        : Classification::where(
          'id',
          $data['destination_classification_id']
        )->firstOrFail();
      $this->changeClass($studentClassMovements, $destinationClass);
    } else {
      StudentMigration::make(currentUser())->revertStudentClassMovements(
        $studentClassMovements
      );
    }

    return $this->ok();
  }

  /** @param StudentClassMovement[] $studentClassMovements */
  private function changeClass($studentClassMovements, $destinationClass)
  {
    $studentMigration = StudentMigration::make(currentUser());
    $batchNo = $studentMigration->generateBatchNo();
    /** @var Student $student */
    foreach ($studentClassMovements as $key => $studentClassMovement) {
      $sourceClass = $studentClassMovement->student->classification;
      if (!$sourceClass && !$destinationClass) {
        continue;
      }
      $studentMigration->migrateStudent(
        $studentClassMovement->student,
        $sourceClass,
        $destinationClass,
        $batchNo
      );
    }
  }
}
