<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;

class StudentTermResultDetailController extends Controller
{
  public function __invoke(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    TermType $term
  ) {
    $institutionUser = currentInstitutionUser();
    abort_if(
      $institutionUser->user_id !== $student->user_id &&
        !$institutionUser->isStaff(),
      403
    );

    $termResult = $student
      ->termResults()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->first();

    if (currentUser()->id == $student->user_id) {
      abort_unless(
        $termResult->is_activated,
        403,
        'This result is not activated'
      );
    }

    $courseResults = $student
      ->courseResults()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->with('course', 'teacher')
      ->get();

    return inertia('institutions/students/student-term-result-detail', [
      'courseResults' => $courseResults,
      'student' => $student->load('user'),
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term,
      'termResult' => $termResult
    ]);
  }
}
