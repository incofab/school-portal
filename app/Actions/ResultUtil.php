<?php
namespace App\Actions;

use App\DTO\AssignedPosition;
use App\Enums\FullTermType;
use App\Enums\TermType;
use Exception;

class ResultUtil
{
  static function getPositionSuffix(int $position)
  {
    $lastChar = $position % 10;
    switch ($lastChar) {
      case 1:
        return 'st';
      case 2:
        return 'nd';
      case 3:
        return 'rd';
      default:
        return 'th';
    }
  }

  static function getRemark(string $grade)
  {
    switch ($grade) {
      case 'A':
        return 'Excellent';
      case 'B':
        return 'Very Good';
      case 'C':
        return 'Good';
      case 'D':
        return 'Fair';
      case 'E':
        return 'Poor';
      case 'F':
        return 'Failed';
      default:
        return 'Unknown';
    }
  }

  static function fullTermMapping(string $fullTerm)
  {
    $term = null;
    $forMidTerm = false;
    switch ($fullTerm) {
      case FullTermType::First->value:
        $term = TermType::First->value;
        $forMidTerm = false;
        break;
      case FullTermType::FirstMid->value:
        $term = TermType::First->value;
        $forMidTerm = true;
        break;
      case FullTermType::Second->value:
        $term = TermType::Second->value;
        $forMidTerm = false;
        break;
      case FullTermType::SecondMid->value:
        $term = TermType::Second->value;
        $forMidTerm = true;
        break;
      case FullTermType::Third->value:
        $term = TermType::Third->value;
        $forMidTerm = false;
        break;
      case FullTermType::ThirdMid->value:
        $term = TermType::Third->value;
        $forMidTerm = true;
        break;
      default:
        return throw new Exception("Invalid full term type ({$fullTerm})");
    }
    return [$term, $forMidTerm];
  }

  /**
   * @param array<string|int, int|float> $arr
   * @return AssignedPosition[] An array of Assigned positions.
   */
  static function assignPositions(array $scoresArray): array
  {
    arsort($scoresArray);
    $positions = [];
    $prevScore = null;
    $prevPosition = null;
    $i = 0;
    foreach ($scoresArray as $id => $score) {
      $i++;
      $position = $i;
      if ($prevScore === $score) {
        $position = $prevPosition;
      }
      $positions[] = new AssignedPosition($id, $score, $position);
      $prevScore = $score;
      $prevPosition = $position;
    }
    return $positions;
  }
}
