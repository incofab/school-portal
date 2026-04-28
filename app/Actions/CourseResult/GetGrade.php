<?php

namespace App\Actions\CourseResult;

use App\Enums\Grade;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\ResultCommentTemplate;
use Illuminate\Support\Collection;

class GetGrade
{
  public static function run(
    float $result,
    Classification|int|null $classification = null,
    ?bool $forMidTerm = false
  ): string {
    $templates = ResultCommentTemplate::getTemplate(
      $classification,
      $forMidTerm
    );

    return self::fromTemplates($result, $templates);
  }

  public static function fromTemplates(float $result, $templates): string
  {
    $matchingTemplate = null;
    foreach ($templates as $template) {
      if ($result >= $template->min && $result <= $template->max) {
        $matchingTemplate = $template;
        break;
      }
    }

    return $matchingTemplate?->grade ?? self::defaultGrade($result);
  }

  public static function defaultGrade(float $result)
  {
    $grade = Grade::F;
    if ($result >= 70) {
      $grade = Grade::A;
    } elseif ($result >= 60) {
      $grade = Grade::B;
    } elseif ($result >= 50) {
      $grade = Grade::C;
    } elseif ($result >= 45) {
      $grade = Grade::D;
    } elseif ($result >= 40) {
      $grade = Grade::E;
    }

    return $grade->value;
  }

  /**
   * Get the distribution of grades across all term results for a class result.
   *
   * @return array<array{grade: string, count: int, percentage: float}>
   */
  public static function getGradeReport(?ClassResultInfo $classResultInfo)
  {
    if (!$classResultInfo) {
      return [];
    }

    $allTermResults = $classResultInfo->termResultsQuery()->get();
    $templates = ResultCommentTemplate::getTemplate(
      $classResultInfo->classification_id,
      $classResultInfo->for_mid_term
    );

    return self::buildGradeReport($allTermResults, 'average', $templates);
  }

  public static function getSubjectGradeReport(
    Classification $classification,
    AcademicSession $academicSession,
    string $term,
    bool $forMidTerm = false
  ): array {
    $templates = ResultCommentTemplate::getTemplate(
      $classification,
      $forMidTerm
    );
    $grades = self::getOrderedGrades($templates);

    $courseResultInfo = CourseResultInfo::query()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm)
      ->with('course')
      ->get()
      ->keyBy('course_id');

    $courseResults = CourseResult::query()
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $academicSession->id)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm)
      ->with('course')
      ->get()
      ->groupBy('course_id');

    $reportRows = [];
    foreach ($courseResultInfo as $courseId => $info) {
      $row = [
        'course_id' => $courseId,
        'course' => $info->course,
        'course_title' => $info->course?->title ?? '-'
      ];

      foreach ($grades as $grade) {
        $row['grades'][$grade] = 0;
      }

      foreach ($courseResults->get($courseId, collect()) as $courseResult) {
        $grade = self::fromTemplates((float) $courseResult->result, $templates);
        if (!array_key_exists($grade, $row['grades'])) {
          $row['grades'][$grade] = 0;
        }
        $row['grades'][$grade]++;
      }

      $reportRows[] = $row;
    }

    return [
      'grades' => array_values(
        array_unique([
          ...$grades,
          ...collect($reportRows)
            ->flatMap(fn($row) => array_keys($row['grades']))
            ->all()
        ])
      ),
      'rows' => $reportRows
    ];
  }

  /**
   * @param  Collection<int, mixed>|\Illuminate\Support\Collection<int, mixed>  $results
   * @param  Collection<int, ResultCommentTemplate>  $templates
   * @return array<array{grade: string, count: int, percentage: float}>
   */
  private static function buildGradeReport(
    iterable $results,
    string $scoreKey,
    $templates
  ): array {
    $gradeReport = [];
    $counts = [];
    $total = 0;

    foreach ($results as $result) {
      $grade = self::fromTemplates(
        (float) data_get($result, $scoreKey),
        $templates
      );
      $counts[$grade] = ($counts[$grade] ?? 0) + 1;
      $total++;
    }

    foreach ($counts as $grade => $count) {
      $gradeReport[] = [
        'grade' => $grade,
        'count' => $count,
        'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0
      ];
    }

    $gradeOrder = array_flip(self::getOrderedGrades($templates));
    usort(
      $gradeReport,
      fn($a, $b) => ($gradeOrder[$a['grade']] ?? PHP_INT_MAX) <=>
        ($gradeOrder[$b['grade']] ?? PHP_INT_MAX)
    );

    return $gradeReport;
  }

  /**
   * @param  Collection<int, ResultCommentTemplate>  $templates
   * @return string[]
   */
  public static function getOrderedGrades($templates): array
  {
    $templateGrades = collect($templates)
      ->sortByDesc('max')
      ->pluck('grade')
      ->filter()
      ->unique()
      ->values()
      ->all();

    if (!empty($templateGrades)) {
      return $templateGrades;
    }

    return array_map(fn($grade) => $grade->value, Grade::cases());
  }
}
