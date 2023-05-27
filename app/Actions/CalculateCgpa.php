<?php
namespace App\Actions;

use App\Enums\Grade;
use App\Models\CourseResult;
use Illuminate\Database\Eloquent\Collection;

class CalculateCgpa
{
  function __construct(private Collection|array $courseResults)
  {
  }

  public static function run(Collection|array $courseRegistrations): array
  {
    $obj = new self($courseRegistrations);
    return $obj->execute();
  }

  public function execute()
  {
    $totalQualityPoints = 0;
    $totalCreditUnits = 0;

    foreach ($this->courseResults as $key => $courseResult) {
      $totalQualityPoints += $this->getQualityPoint($courseResult);
      $totalCreditUnits +=
        $courseResult->courseRegistration->course->credit_unit;
    }

    return [
      'total_quality_points' => $totalQualityPoints,
      'total_credit_units' => $totalCreditUnits,
      'cgpa' =>
        $totalCreditUnits > 0
          ? round($totalQualityPoints / $totalCreditUnits, 2)
          : 0
    ];
  }

  private function getQualityPoint(CourseResult $courseResult)
  {
    return $courseResult->courseRegistration->course->credit_unit *
      $this->getGradePoint($courseResult);
  }

  private function getGradePoint(CourseResult $courseResult)
  {
    return match (strtoupper($courseResult->grade)) {
      Grade::A->value => 5,
      Grade::B->value => 4,
      Grade::C->value => 3,
      Grade::D->value => 2,
      Grade::E->value => 1,
      Grade::F->value => 0
    };
  }
}
