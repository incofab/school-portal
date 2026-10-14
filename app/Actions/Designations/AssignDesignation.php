<?php

namespace App\Actions\Designations;

use App\Enums\DesignationScope;
use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Models\AcademicSession;
use App\Models\Designation;
use App\Models\DesignationAssignment;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignDesignation
{
  public function execute(
    Institution $institution,
    Designation $designation,
    InstitutionUser $person,
    AcademicSession $session,
    int $assignedBy,
    ?string $note = null
  ): DesignationAssignment {
    return DB::transaction(function () use (
      $institution,
      $designation,
      $person,
      $session,
      $assignedBy,
      $note
    ) {
      $designation = Designation::query()
        ->whereKey($designation->id)
        ->lockForUpdate()
        ->firstOrFail();
      if (
        (int) $designation->institution_id !== (int) $institution->id ||
        !$designation->is_active
      ) {
        throw ValidationException::withMessages([
          'designation_id' => 'This designation is unavailable for assignment.'
        ]);
      }
      if (
        (int) $person->institution_id !== (int) $institution->id ||
        $person->status !== InstitutionUserStatus::Active
      ) {
        throw ValidationException::withMessages([
          'institution_user_id' =>
            'Choose an active person from this institution.'
        ]);
      }

      $classificationId = null;
      if ($designation->is_for_students) {
        $student = $person->student;
        if (
          $person->type !== InstitutionUserType::Student ||
          !$student ||
          (int) $student->classification?->institution_id !==
            (int) $institution->id
        ) {
          throw ValidationException::withMessages([
            'institution_user_id' =>
              'This designation can only be assigned to an active student in this institution.'
          ]);
        }
        $classificationId = $student->classification_id;
        if (
          !$designation
            ->eligibleClasses()
            ->whereKey($classificationId)
            ->exists()
        ) {
          throw ValidationException::withMessages([
            'institution_user_id' =>
              'This student is not in an eligible class for the designation.'
          ]);
        }
      } elseif (
        !in_array($person->type, InstitutionUserType::staffTypes(), true) ||
        $person->student()->exists()
      ) {
        throw ValidationException::withMessages([
          'institution_user_id' =>
            'Choose an active staff member for this designation.'
        ]);
      }

      $duplicate = DesignationAssignment::query()
        ->where('designation_id', $designation->id)
        ->where('institution_user_id', $person->id)
        ->where('academic_session_id', $session->id)
        ->whereNull('ended_at')
        ->exists();
      if ($duplicate) {
        throw ValidationException::withMessages([
          'institution_user_id' =>
            'This person already holds this designation in the selected academic session.'
        ]);
      }

      $active = DesignationAssignment::query()
        ->where('designation_id', $designation->id)
        ->where('academic_session_id', $session->id)
        ->whereNull('ended_at')
        ->when(
          $designation->scope === DesignationScope::Classification,
          fn($query) => $query->where('classification_id', $classificationId)
        );
      $count = $active->count();
      if ($count >= $designation->max_holders) {
        throw ValidationException::withMessages([
          'institution_user_id' =>
            'The holder limit for this designation has been reached for the selected scope and session.'
        ]);
      }

      return DesignationAssignment::create([
        'institution_id' => $institution->id,
        'designation_id' => $designation->id,
        'institution_user_id' => $person->id,
        'classification_id' => $classificationId,
        'academic_session_id' => $session->id,
        'assigned_by_user_id' => $assignedBy,
        'assigned_at' => now(),
        'note' => $note
      ]);
    });
  }
}
