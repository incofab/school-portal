<?php

namespace App\Actions\Designations;

use App\Models\DesignationAssignment;
use App\Models\Institution;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EndDesignationAssignment
{
  public function execute(
    Institution $institution,
    DesignationAssignment $assignment
  ): DesignationAssignment {
    return DB::transaction(function () use ($institution, $assignment) {
      $assignment = DesignationAssignment::query()
        ->whereKey($assignment->id)
        ->lockForUpdate()
        ->firstOrFail();
      if ((int) $assignment->institution_id !== (int) $institution->id) {
        throw ValidationException::withMessages([
          'designation_assignment' =>
            'This assignment does not belong to the current institution.'
        ]);
      }
      $assignment->ended_at ??= now();
      $assignment->save();
      return $assignment;
    });
  }
}
