<?php
namespace App\Actions\CourseResult;

use App\Enums\Grade;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\ResultCommentTemplate;

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
   * @param ClassResultInfo|null $classResultInfo
   * @return array<array{grade: string, count: int, percentage: float}>
   */
  public static function getGradeReport(?ClassResultInfo $classResultInfo)
  {
    if (!$classResultInfo) {
      return [];
    }

    $gradeReport = [];
    $allTermResults = $classResultInfo->termResultsQuery()->get();
    $templates = \App\Models\ResultCommentTemplate::getTemplate(
      $classResultInfo->classification_id,
      $classResultInfo->for_mid_term
    );

    $counts = [];
    foreach ($allTermResults as $tr) {
      $grade = self::fromTemplates($tr->average, $templates);
      $counts[$grade] = ($counts[$grade] ?? 0) + 1;
    }

    $total = count($allTermResults);
    foreach ($counts as $grade => $count) {
      $gradeReport[] = [
        'grade' => $grade,
        'count' => $count,
        'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0
      ];
    }

    // Sort by grade A, B, C...
    usort($gradeReport, fn($a, $b) => strcmp($a['grade'], $b['grade']));
    return $gradeReport;
  }
}
