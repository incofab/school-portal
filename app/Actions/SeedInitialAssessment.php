<?php
namespace App\Actions;

use App\Models\Institution;

class SeedInitialAssessment
{
  public static function run(Institution $institution)
  {
    $institution
      ->assessments()
      ->firstOrCreate(['title' => 'first_assessment'], ['max' => 20]);
    $institution
      ->assessments()
      ->firstOrCreate(['title' => 'second_assessment'], ['max' => 20]);
    // $institution
    //   ->assessments()
    //   ->firstOrCreate(['title' => 'exam'], ['max' => 60]);
  }
}
