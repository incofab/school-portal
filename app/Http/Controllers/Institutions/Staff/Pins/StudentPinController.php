<?php

namespace App\Http\Controllers\Institutions\Staff\Pins;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Pin;
use App\Models\Student;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\PinUITableFilters;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** @deprecated We're not using this anymore. */
class StudentPinController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin]);
  }

  public function indexTiles(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    abort_if(
      $classification->institution_id !== $institution->id,
      403,
      'Access denied'
    );
    $query = PinUITableFilters::make(
      [...$request->all(), 'classification' => $classification->id],
      Pin::query()
    )
      ->forCurrentTerm()
      ->filterQuery()
      ->getQuery()
      ->with('student', 'student.user', 'academicSession');

    return Inertia::render('institutions/pins/list-student-pin-tiles', [
      'classification' => $classification,
      'pins' => $query
        ->oldest('users.last_name')
        ->take(1000)
        ->get()
    ]);
  }

  private function recordStudentPin(Institution $institution, Student $student)
  {
    $settingHandler = SettingsHandler::makeFromRoute();
    $academicSessionId = $settingHandler->getCurrentAcademicSession();
    $term = $settingHandler->getCurrentTerm();

    Pin::query()->firstOrCreate(
      [
        'institution_id' => $institution->id,
        'student_id' => $student->id,
        'term' => $term,
        'academic_session_id' => $academicSessionId
      ],
      ['pin' => Pin::generatePin()]
    );
  }

  public function storeStudentPin(
    Request $request,
    Institution $institution,
    Student $student
  ) {
    abort_if(
      $student->institutionUser->institution_id !== $institution->id,
      403,
      'Access denied'
    );

    $this->recordStudentPin($institution, $student);

    return $this->ok();
  }

  public function storeClassStudentPin(
    Request $request,
    Institution $institution,
    Classification $classification
  ) {
    abort_if(
      $classification->institution_id !== $institution->id,
      403,
      'Access denied'
    );

    $students = $classification->students;
    foreach ($students as $key => $student) {
      $this->recordStudentPin($institution, $student);
    }

    return $this->ok();
  }
}
