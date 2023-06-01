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

    $courseResults = $student
      ->courseResults()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->with('course', 'teacher')
      ->get();

    $termResult = TermResult::query()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->first();

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
