<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\ClassResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use App\Support\UITableFilters\TermResultUITableFilters;

class ViewResultSheetController extends Controller
{
  public function __invoke(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm
  ) {
    $institutionUser = currentInstitutionUser();
    abort_if(
      $institutionUser->user_id !== $student->user_id &&
        !$institutionUser->isAdmin(),
      403
    );
    $params = [
      'institution_id' => $institution->id,
      'classification' => $classification->id,
      'term' => $term,
      'academicSession' => $academicSession->id,
      'forMidTerm' => $forMidTerm
    ];

    $termResult = TermResultUITableFilters::make(
      $params,
      $student->termResults()->getQuery()
    )
      ->filterQuery()
      ->getQuery()
      ->first();

    abort_unless($termResult, 404, 'Result not found');

    if (currentUser()->id == $student->user_id) {
      abort_unless(
        $termResult->is_activated,
        403,
        'This result is not activated'
      );
    }

    $courseResults = CourseResultsUITableFilters::make(
      $params,
      $student->courseResults()->getQuery()
    )
      ->filterQuery()
      ->getQuery()
      ->with('course', 'teacher')
      ->get();

    $courseResultInfo = CourseResultInfoUITableFilters::make(
      $params,
      CourseResultInfo::query()
    )
      ->filterQuery()
      ->getQuery()
      ->get();
    $courseResultInfoData = [];
    foreach ($courseResultInfo as $key => $value) {
      $courseResultInfoData[$value->course_id] = $value;
    }

    $classResultInfo = ClassResultInfoUITableFilters::make(
      $params,
      ClassResultInfo::query()
    )
      ->filterQuery()
      ->getQuery()
      ->first();

    $assessments = Assessment::query()
      ->forMidTerm($termResult->for_mid_term)
      ->forTerm($term)
      ->get();

    $viewData = [
      'institution' => currentInstitution(),
      'courseResults' => $courseResults,
      'student' => $student->load('user'),
      'classification' => $classification,
      'academicSession' => $academicSession,
      'term' => $term,
      'termResult' => $termResult,
      'classResultInfo' => $classResultInfo,
      'courseResultInfoData' => $courseResultInfoData,
      'resultDetails' => $this->getResultDetails($classResultInfo, $termResult),
      'assessments' => $assessments,
      'learningEvaluations' => $institution
        ->learningEvaluations()
        ->with('learningEvaluationDomain')
        ->orderBy('learning_evaluation_domain_id')
        ->get()
    ];

    $setting = SettingsHandler::makeFromRoute();
    return inertia(
      "institutions/result-sheets/{$setting->getResultTemplate()}",
      $viewData
    );
    // if (request('download')) {
    //   return $this->downloadAsPDF($viewData);
    // }
    // return view('student-result-sheet', $viewData);
  }

  private function getResultDetails(
    ClassResultInfo $classResultInfo,
    TermResult $termResult
  ) {
    return [
      ['label' => "Student's Total Score", 'value' => $termResult->total_score],
      [
        'label' => 'Maximum Total Score',
        'value' => $classResultInfo->max_obtainable_score
      ],
      ['label' => "Student's Average Score", 'value' => $termResult->average],
      ['label' => 'Class Average Score', 'value' => $classResultInfo->average]
    ];
  }

  // private function downloadAsPDF(array $viewData)
  // {
  //   $student = $viewData['student'];
  //   $academicSession = $viewData['academicSession'];
  //   // dd('dksmdsd');
  //   $pdf = Pdf::loadView('student-result-sheet', $viewData);
  //   $filename = "{$student->user->full_name} {$academicSession->title} {$viewData['term']} term result.pdf";
  //   $filename = str_replace(['/'], ['-'], $filename);
  //   // dd($filename);
  //   return $pdf->download($filename);
  // }
}
