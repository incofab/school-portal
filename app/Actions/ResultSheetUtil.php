<?php
namespace App\Actions;

class ResultSheetUtil
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
}
