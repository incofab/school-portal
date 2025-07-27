<?php
namespace App\Actions\Result;

use App\Enums\ResultCommentTemplateType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Models\Student;
use App\Models\TermDetail;
use App\Models\TermResult;
use App\Support\UITableFilters\ClassResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultInfoUITableFilters;
use App\Support\UITableFilters\CourseResultsUITableFilters;
use App\Support\UITableFilters\TermResultUITableFilters;

class GetViewResultSheetData
{
  public static function run(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm
  ) {
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

    // if (currentUser()->id == $student->user_id) {
    //   abort_unless(
    //     $termResult->is_activated,
    //     403,
    //     'This result is not activated'
    //   );
    // }

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
      'resultDetails' => self::getResultDetails($classResultInfo, $termResult),
      'assessments' => $assessments,
      'resultCommentTemplate' => $resultCommentTemplate,
      'termDetail' => TermDetail::query()
        ->forTermResult($termResult)
        ->first(),
      'learningEvaluations' => $institution
        ->learningEvaluations()
        ->with('learningEvaluationDomain')
        ->orderBy('learning_evaluation_domain_id')
        ->get()
    ];
    return $viewData;
  }

  private static function getResultDetails(
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
