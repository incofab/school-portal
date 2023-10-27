<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Actions\StudentMigration;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\Classification;
use App\Models\Institution;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class UpdateStudentClassController extends Controller
{
  function migrateClassStudents(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    $data = $request->validate([
      'destination_class' => [
        'nullable',
        'integer',
        Rule::requiredIf(!$request->move_to_alumni)
      ],
      'move_to_alumni' => ['nullable', 'boolean']
    ]);

    $classMigration = StudentMigration::make(currentUser());

    if ($request->move_to_alumni) {
      $classMigration->migrateClass($classification);
      return $this->ok();
    }

    $destinationClassification = Classification::query()->findOrFail(
      $data['destination_class']
    );

    abort_if(
      $destinationClassification->students()->exists(),
      Response::HTTP_NOT_ACCEPTABLE,
      'The destination class already contains some students'
    );

    $classMigration->migrateClass($classification, $destinationClassification);

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

    $studentMigration = StudentMigration::make(currentUser());

    if ($request->move_to_alumni) {
      $studentMigration->moveToAlumni([$student]);
    } else {
      $destinationClassification = Classification::query()->findOrFail(
        $data['destination_class']
      );
      $studentMigration->migrateStudent($student, $destinationClassification);
    }

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

    if ($request->move_to_alumni) {
      $studentMigration->moveToAlumni($students);
    } else {
      $destinationClassification = Classification::query()->findOrFail(
        $data['destination_class']
      );
      $studentMigration->migrateStudents($students, $destinationClassification);
    }

    return $this->ok();
  }
}
