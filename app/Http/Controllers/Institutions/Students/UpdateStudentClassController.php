<?php
namespace App\Http\Controllers\Institutions\Students;

use App\Actions\ClassMigration;
use App\Actions\UpdateStudentClass;
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

    $classMigration = ClassMigration::make($classification);

    if ($request->move_to_alumni) {
      $classMigration->moveToAlumni();
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

    $classMigration->migrate($destinationClassification);

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

    $updateStudentClass = UpdateStudentClass::make($student);
    if ($request->move_to_alumni) {
      $updateStudentClass->moveToAlumni();
    } else {
      $destinationClassification = Classification::query()->findOrFail(
        $data['destination_class']
      );
      $updateStudentClass->changeClass($destinationClassification);
    }

    return $this->ok();
  }
}
