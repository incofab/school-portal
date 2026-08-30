<?php

namespace App\Actions\Result;

use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Assessment;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\ResultCommentTemplate;
use App\Models\SessionResult;
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
  public static function runByTermResult(TermResult $termResult)
  {
    return self::getSheetData($termResult);
  }

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
      ->with('classification.classificationGroup')
      ->first();

    abort_unless($termResult, 404, 'Result not found');

    return self::getSheetData($termResult);
  }

  public static function getSheetData(TermResult $termResult)
  {
    $termResult->loadMissing(
      'classification.classificationGroup',
      'student',
      'academicSession',
      'institution'
    );
    $institution = $termResult->institution;
    $student = $termResult->student;
    $classification = $termResult->classification;
    $academicSession = $termResult->academicSession;
    $term = $termResult->term;
    $forMidTerm = $termResult->for_mid_term;
    $params = [
      'institution_id' => $institution->id,
      'classification' => $classification->id,
      'term' => $term,
      'academicSession' => $academicSession->id,
      'forMidTerm' => $termResult->for_mid_term
    ];

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
      $classification,
      true
    );
    $resultCommentTemplate = ResultCommentTemplate::getTemplate(
      $classification,
      $forMidTerm
    );

    $termDetail = TermDetail::query()
      ->forTermResult($termResult)
      ->first();
    $settingsHandler = SettingsHandler::makeFromInstitution($institution);
    $classification->loadMissing('classificationGroup');

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
      'subjectTermTotals' => self::getSubjectTermTotals(
        $termResult,
        $courseResults->pluck('course_id')->all()
      ),
      'termTotalsByTerm' => self::getTermTotalsByTerm($termResult),
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
        ->get(),
      'sessionResult' => self::sessionResult(
        $institution,
        $student,
        $classification,
        $academicSession
      )
    ];

    return $viewData;
  }

  private static function getResultDetails(
    ClassResultInfo $classResultInfo,
    TermResult $termResult
  ) {
    $studentTitle =
      $termResult->classification?->classificationGroup?->studentPossessiveTitle() ??
      ClassificationGroup::possessiveTitle(
        ClassificationGroup::singularizeTitle(
          ClassificationGroup::DEFAULT_STUDENT_TITLE
        )
      );

    return [
      [
        'label' => "{$studentTitle} Total Score",
        'value' => $termResult->total_score
      ],
      [
        'label' => 'Maximum Total Score',
        'value' => $classResultInfo->max_obtainable_score
      ],
      [
        'label' => "{$studentTitle} Average Score",
        'value' => $termResult->average
      ],
      ['label' => 'Class Average Score', 'value' => $classResultInfo->average]
    ];
  }

  /**
   * @param  array<int, int>  $courseIds
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

  /**
   * @param  array<int, int>  $courseIds
   * @return array<int, array{first?: float, second?: float, third?: float}>
   */
  private static function getSubjectTermTotals(
    TermResult $termResult,
    array $courseIds
  ): array {
    if (empty($courseIds)) {
      return [];
    }

    $data = [];
    CourseResult::query()
      ->where('institution_id', $termResult->institution_id)
      ->where('student_id', $termResult->student_id)
      ->where('classification_id', $termResult->classification_id)
      ->where('academic_session_id', $termResult->academic_session_id)
      ->where('for_mid_term', $termResult->for_mid_term)
      ->whereIn('course_id', $courseIds)
      ->whereIn('term', [
        TermType::First->value,
        TermType::Second->value,
        TermType::Third->value
      ])
      ->get(['course_id', 'term', 'result'])
      ->each(function (CourseResult $courseResult) use (&$data) {
        $term = is_string($courseResult->term)
          ? $courseResult->term
          : $courseResult->term->value;

        $data[$courseResult->course_id][$term] = (float) $courseResult->result;
      });

    return $data;
  }

  /**
   * @return array<string, array{total_score?: float, average?: float}>
   */
  private static function getTermTotalsByTerm(TermResult $termResult): array
  {
    return TermResult::query()
      ->where('institution_id', $termResult->institution_id)
      ->where('student_id', $termResult->student_id)
      ->where('classification_id', $termResult->classification_id)
      ->where('academic_session_id', $termResult->academic_session_id)
      ->where('for_mid_term', false)
      ->whereIn('term', [
        TermType::First->value,
        TermType::Second->value,
        TermType::Third->value
      ])
      ->get(['term', 'total_score', 'average'])
      ->mapWithKeys(function (TermResult $result) {
        $term = is_string($result->term) ? $result->term : $result->term->value;

        return [
          $term => [
            'total_score' =>
              $result->total_score === null
                ? null
                : (float) $result->total_score,
            'average' =>
              $result->average === null ? null : (float) $result->average
          ]
        ];
      })
      ->all();
  }

  private static function sessionResult(
    Institution $institution,
    Student $student,
    Classification $classification,
    AcademicSession $academicSession
  ) {
    return SessionResult::query()
      ->where('institution_id', $institution->id)
      ->where('student_id', $student->id)
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->first();
  }
}
