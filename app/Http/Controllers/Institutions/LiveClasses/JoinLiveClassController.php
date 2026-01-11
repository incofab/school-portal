<?php

namespace App\Http\Controllers\Institutions\LiveClasses;

use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\ClassDivision;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\LiveClass;
use App\Support\MorphMap;

class JoinLiveClassController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Student]);
  }

  public function __invoke(Institution $institution, LiveClass $liveClass)
  {
    abort_unless($liveClass->institution_id === $institution->id, 404);
    abort_unless($liveClass->is_active, 403, 'Live class is not active');

    $institutionUser = currentInstitutionUser();
    if ($institutionUser?->isStaff()) {
      return redirect()->away($liveClass->meet_url);
    }

    $student = $institutionUser?->student;
    abort_unless($student, 403, 'Only students can join live classes');

    if (!$this->studentCanJoin($student, $liveClass)) {
      abort(403, 'You are not allowed to join this live class');
    }

    return redirect()->away($liveClass->meet_url);
  }

  private function studentCanJoin($student, LiveClass $liveClass): bool
  {
    if ($liveClass->liveable_type === MorphMap::key(Classification::class)) {
      return $student->classification_id === $liveClass->liveable_id;
    }

    $classification = $student
      ->classification()
      ->with('classDivisions')
      ->first();
    if (
      $liveClass->liveable_type === MorphMap::key(ClassificationGroup::class)
    ) {
      return $classification?->classification_group_id ===
        $liveClass->liveable_id;
    }

    if ($liveClass->liveable_type === MorphMap::key(ClassDivision::class)) {
      return $classification?->classDivisions
        ?->pluck('id')
        ->contains($liveClass->liveable_id);
    }

    return false;
  }
}
