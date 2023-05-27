<?php
namespace App\Actions\CourseResult;

use App\Enums\Grade;

class GetGrade
{
  public static function run(float $result)
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
