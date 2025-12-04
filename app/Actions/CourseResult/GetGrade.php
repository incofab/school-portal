<?php
namespace App\Actions\CourseResult;

use App\Enums\Grade;
use App\Models\Classification;
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
}
