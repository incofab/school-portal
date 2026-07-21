<?php

namespace App\Actions\Result;

use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Models\Student;
use App\Models\TermDetail;
use App\Models\TermResult;
use App\Support\SettingsHandler;
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
      ->orderByCourseOrder()
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

    $assessments = Assessment::getAssessments(
      $term,
      $termResult->for_mid_term,
      $classification
    );
    $resultCommentTemplate = ResultCommentTemplate::getTemplate(
      $classification,
      $forMidTerm
    );

    $termDetail = TermDetail::query()
      ->forTermResult($termResult)
      ->first();
    $settingsHandler = SettingsHandler::makeFromInstitution($institution);

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
      'subjectCumulativeAverages' => self::getSubjectCumulativeAverages(
        $institution,
        $student,
        $classification,
        $academicSession,
        $termResult->for_mid_term,
        $courseResults->pluck('course_id')->all()
      ),
      'resultDetails' => self::getResultDetails($classResultInfo, $termResult),
      'assessments' => $assessments,
      'resultCommentTemplate' => $resultCommentTemplate,
      'termDetail' => $termDetail,
      'showExamResult' => $settingsHandler->shouldDisplayExamResults(
        $termDetail,
        $termResult->for_mid_term
      ),
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

  /**
   * @param array<int, int> $courseIds
   * @return array<int, float>
   */
  private static function getSubjectCumulativeAverages(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession,
    bool $forMidTerm,
    array $courseIds
  ): array {
    if (empty($courseIds)) {
      return [];
    }

    return CourseResult::query()
      ->where('institution_id', $institution->id)
      ->where('student_id', $student->id)
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('for_mid_term', $forMidTerm)
      ->whereIn('course_id', $courseIds)
      ->whereIn('term', ['first', 'second', 'third'])
      ->selectRaw('course_id, ROUND(AVG(result), 2) as cumulative_average')
      ->groupBy('course_id')
      ->pluck('cumulative_average', 'course_id')
      ->map(fn($average) => (float) $average)
      ->all();
  }
}
