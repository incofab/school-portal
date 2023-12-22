<?php

namespace App\Http\Controllers\Institutions\Students;

use App\Actions\Result\GetViewResultSheetData;
use App\Enums\ResultCommentTemplateType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Models\Student;
use App\Models\TermResult;
use App\Support\SettingsHandler;
use App\Support\UITableFilters\ClassResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use App\Support\UITableFilters\TermResultUITableFilters;
use Http;
use Illuminate\Http\Request;
use URL;

class ViewResultSheetController extends Controller
{
  public function viewResult(
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

    $viewData = GetViewResultSheetData::run(
      $institution,
      $student,
      $classification,
      $academicSession,
      $term,
      $forMidTerm
    );

    $termResult = $viewData['termResult'] ?? null;
    abort_unless($termResult, 404, 'Result not found');

    if (currentUser()->id == $student->user_id) {
      abort_unless(
        $termResult->is_activated,
        403,
        'This result is not activated'
      );
    }

    $viewData['signed_url'] = URL::temporarySignedRoute(
      'institutions.students.result-sheet.signed',
      now()->addHour(),
      [
        $institution->uuid,
        $student,
        $classification,
        $academicSession,
        $term,
        $forMidTerm ? 1 : 0
      ]
    );

    return $this->display($viewData);
  }

  public function viewResultSigned(
    Request $request,
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm
  ) {
    abort_unless($request->hasValidSignature(), 403, 'Access denied');

    $viewData = GetViewResultSheetData::run(
      $institution,
      $student,
      $classification,
      $academicSession,
      $term,
      $forMidTerm
    );
    return $this->display($viewData);
  }

  private function display(array $viewData)
  {
    $setting = SettingsHandler::makeFromRoute();
    return inertia(
      "institutions/result-sheets/{$setting->getResultTemplate()}",
      $viewData
    );
  }

  function pdfBridge(Request $request)
  {
    $data = $request->validate([
      'url' => ['required', 'string'],
      'filename' => ['required', 'string']
    ]);
    $res = Http::post(config('services.pdf.url'), $data);
    abort_unless(
      $res->ok(),
      401,
      'Initial PDF error encountered, Alternative means will be used'
    );
    return $this->ok(['filename' => $request->filename]);
  }

  /** @deprecated */
  public function deprecated(
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
      ->with('classification')
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
    $resultCommentTemplate = ResultCommentTemplate::query()
      ->where(
        fn($q) => $q
          ->whereNull('type')
          ->orWhere(
            'type',
            $forMidTerm
              ? ResultCommentTemplateType::MidTermResult
              : ResultCommentTemplateType::FullTermResult
          )
      )
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
      'resultCommentTemplate' => $resultCommentTemplate,
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

  /** @deprecated */
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
}
