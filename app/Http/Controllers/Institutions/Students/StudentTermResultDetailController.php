<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Assessment;
use App\Models\Institution;
use App\Models\Student;

class StudentTermResultDetailController extends Controller
{
  public function __invoke(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    TermType $term,
    bool $forMidTerm
  ) {
    $institutionUser = currentInstitutionUser();
    if ($institutionUser->isStudent()) {
      return redirect()->route('institutions.students.result-sheet', [
        $institution->uuid,
        $student,
        $classification,
        $academicSession,
        $term,
        $forMidTerm ? 1 : 0
      ]);
    }
    abort_if(
      $institutionUser->user_id !== $student->user_id &&
        !$institutionUser->isAdmin() &&
        !$institutionUser->isTeacher(),
      403
    );

    $termResult = $student
      ->termResults()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm)
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
      ->where('for_mid_term', $termResult->for_mid_term)
      ->with('course', 'teacher')
      ->get();

    $assessments = Assessment::query()
      ->forMidTerm($termResult->for_mid_term)
      ->forTerm($term->value)
      ->get();

    return inertia('institutions/students/student-term-result-detail', [
      'courseResults' => $courseResults,
      'student' => $student->load('user'),
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term,
      'termResult' => $termResult,
      'assessments' => $assessments,
      'learningEvaluations' => $institution
        ->learningEvaluations()
        ->with('learningEvaluationDomain')
        ->orderBy('learning_evaluation_domain_id')
        ->get()
    ]);
  }
}
