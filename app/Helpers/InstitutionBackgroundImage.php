<?php
namespace App\Helpers;

use App\Models\InstitutionGroup;

class InstitutionBackgroundImage
{
  public static function getBackgroundImage()
  {
    //= Get the current institutionGroup and its banner.
    $currentDomain = request()->getHost();
    $institutionGroup = InstitutionGroup::where(
      'website',
      $currentDomain
    )->first();

    return $institutionGroup?->banner;
  }
}
