<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Student;

class ShowTranscriptController extends Controller
{
  public function __invoke(Institution $institution, Student $student)
  {
    $institutionUser = currentInstitutionUser();
    abort_if(
      $institutionUser->user_id !== $student->user_id &&
        !$institutionUser->isAdmin(),
      403
    );

    $student->load('user', 'classification');
    $courseResults = $student
      ->courseResults()
      ->with('course')
      ->get();
    $termResults = $student
      ->termResults()
      ->with('academicSession', 'classification')
      ->get();
    $sessionResults = $student
      ->sessionResults()
      ->with('classification', 'academicSession')
      ->get();

    return inertia('institutions/student-transcript', [
      'student' => $student,
      'courseResults' => $courseResults,
      'sessionResults' => $sessionResults,
      'termResults' => $termResults
    ]);
  }
}
