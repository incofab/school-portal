<?php

namespace App\Http\Controllers\Institutions\Classifications;

use App\Actions\StudentMigration;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Support\Audit\AcademicActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateStudentClassController extends Controller
{
  public function migrateClassStudents(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    $data = $request->validate([
      'destination_class' => [
        'nullable',
        'integer',
        Rule::requiredIf(!$request->move_to_alumni),
        function ($attr, $value, $fail) {
          if (Student::where('classification_id', $value)->exists()) {
            $fail('The destination class already contains some students');
          }
        }
      ],
      'move_to_alumni' => ['nullable', 'boolean']
    ]);

    $destinationClassification = null;
    if ($request->destination_class) {
      $destinationClassification = Classification::query()->findOrFail(
        $data['destination_class']
      );
    }

    StudentMigration::make(currentUser())->migrateClass(
      $classification,
      $destinationClassification
    );

    return $this->ok();
  }

  public function changeStudentClass(
    Request $request,
    Institution $institution,
    Student $student
  ) {
    $data = $request->validate([
      'move_to_alumni' => ['nullable', 'boolean'],
      'destination_class' => [
        'nullable',
        'integer',
        Rule::requiredIf(!$request->move_to_alumni)
      ]
    ]);

    $destinationClassification = null;
    if ($request->destination_class) {
      $destinationClassification = Classification::query()->findOrFail(
        $data['destination_class']
      );
    }

    StudentMigration::make(currentUser())->migrateStudent(
      $student,
      $student->classification,
      $destinationClassification
    );

    return $this->ok();
  }

  public function changeMultipleStudentClassView(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    return inertia(
      'institutions/classifications/change-multiple-students-class',
      [
        'students' => $classification
          ->students()
          ->with('user', 'classification')
          ->get()
      ]
    );
  }

  public function changeMultipleStudentClass(
    Request $request,
    Institution $institution
  ) {
    $data = $request->validate([
      'move_to_alumni' => ['nullable', 'boolean'],
      'students' => ['required', 'array', 'min:1'],
      'students.*' => ['required', 'integer'],
      'destination_class' => [
        'nullable',
        'integer',
        Rule::requiredIf(!$request->move_to_alumni)
      ]
    ]);

    $students = Student::query()
      ->whereIn('id', $data['students'])
      ->get();

    abort_unless($students->count() > 0, 401, 'Students not found');
    $studentMigration = StudentMigration::make(currentUser());

    $destinationClassification = null;
    if ($request->destination_class) {
      $destinationClassification = Classification::query()->findOrFail(
        $data['destination_class']
      );
    }

    $batchNo = $studentMigration->generateBatchNo();
    $count = 0;
    /** @var Student $student */
    foreach ($students as $key => $student) {
      $studentMigration->migrateStudent(
        $student,
        $student->classification,
        $destinationClassification,
        $batchNo
      );
      $count++;
    }

    app(AcademicActivityLogger::class)->studentMovementSummary(
      $institution,
      'student.multiple_class_changed',
      'changed_multiple_classes',
      'Multiple student classes changed.',
      [
        'batch_no' => $batchNo,
        'student_count' => $count,
        'student_ids' => $students
          ->pluck('id')
          ->values()
          ->all(),
        'destination_classification_id' => $destinationClassification?->id,
        'destination_classification_title' => $destinationClassification?->title
      ],
      $destinationClassification
    );

    return $this->ok();
  }
}
