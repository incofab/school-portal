<?php
namespace App\Actions;

use App\Models\Assessment;
use App\Models\ClassDivision;

/** A dummy class for running temporary codes */
class Temp
{
  function __construct()
  {
  }

  public static function run()
  {
    $classDivisions = ClassDivision::query()
      ->whereHas('assessments')
      ->with('assessments', 'classifications')
      ->get();
    /** @var ClassDivision $classDivision */
    foreach ($classDivisions as $key => $classDivision) {
      /** @var Assessment $assessment */
      foreach ($classDivision->assessments as $key => $assessment) {
        $ret = $assessment->classifications()->syncWithoutDetaching(
          $classDivision
            ->classifications()
            ->get()
            ->pluck('id')
            ->toArray()
        );
      }
    }
  }

  public static function delete()
  {
    ClassDivision::query()
      ->whereHas('assessments')
      ->with('assessments')
      ->forceDelete();
  }
}
