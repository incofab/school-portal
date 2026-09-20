<?php

namespace App\Services\AI;

use App\Enums\InstitutionPermission;
use App\DTO\AI\AssistantActorContext;
use App\Models\Classification;
use App\Models\CourseTeacher;
use App\Models\GuardianStudent;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;

class AssistantToolScope
{
  public function students(AssistantActorContext $context): ?Builder
  {
    $institutionUser = $context->institutionUser;

    if ($context->isGuest || !$context->institution || !$institutionUser) {
      return null;
    }

    $query = Student::query()
      ->whereHas(
        'institutionUser',
        fn($query) => $query->where('institution_id', $context->institution->id)
      )
      ->with(['user', 'classification']);

    if (
      $institutionUser->isAdmin() ||
      ($institutionUser->isStaff() && !$institutionUser->isTeacher())
    ) {
      return $query;
    }

    if ($institutionUser->isTeacher()) {
      $assignedClassIds = Classification::query()
        ->where('institution_id', $context->institution->id)
        ->where(function ($query) use ($context) {
          $query->where('form_teacher_id', $context->userId())->orWhereIn(
            'id',
            CourseTeacher::query()
              ->where('institution_id', $context->institution->id)
              ->where('user_id', $context->userId())
              ->select('classification_id')
          );
        })
        ->select('id');

      return $query->whereIn('classification_id', $assignedClassIds);
    }

    if ($institutionUser->isGuardian()) {
      return $query->whereIn(
        'students.id',
        GuardianStudent::query()
          ->where('institution_id', $context->institution->id)
          ->where('guardian_user_id', $context->userId())
          ->select('student_id')
      );
    }

    return $query->where('students.user_id', $context->userId());
  }

  public function canAccessInstitution(AssistantActorContext $context): bool
  {
    return !$context->isGuest &&
      $context->institution !== null &&
      $context->institutionUser !== null &&
      $context->institutionUser->isActive();
  }

  public function canViewAttendanceSummary(AssistantActorContext $context): bool
  {
    if (!$this->canAccessInstitution($context)) {
      return false;
    }

    $institutionUser = $context->institutionUser;

    return $institutionUser->hasInstitutionPermission(
      InstitutionPermission::ManageAttendance
    ) ||
      $institutionUser->isStudent() ||
      $institutionUser->isGuardian();
  }

  public function canViewFeeSummary(AssistantActorContext $context): bool
  {
    if (!$this->canAccessInstitution($context)) {
      return false;
    }

    $institutionUser = $context->institutionUser;

    return $institutionUser->hasInstitutionPermission(
      InstitutionPermission::ManageFees
    ) ||
      $institutionUser->isStudent() ||
      $institutionUser->isGuardian();
  }

  public function canViewClassSummary(AssistantActorContext $context): bool
  {
    if (!$this->canAccessInstitution($context)) {
      return false;
    }

    return $context->institutionUser->isAdmin() ||
      $context->institutionUser->isTeacher();
  }

  public function canViewTeacherAssignments(
    AssistantActorContext $context
  ): bool {
    if (!$this->canAccessInstitution($context)) {
      return false;
    }

    return $context->institutionUser->isAdmin() ||
      $context->institutionUser->isTeacher();
  }

  public function resolveStudent(
    AssistantActorContext $context,
    string $value
  ): ?Student {
    $students = $this->students($context);
    if (!$students) {
      return null;
    }

    if (strtolower($value) === 'me') {
      return $students->where('students.user_id', $context->userId())->first();
    }

    $student = (clone $students)->where('students.code', $value)->first();
    if ($student) {
      return $student;
    }

    return $students
      ->get()
      ->first(
        fn(Student $student) => strtolower(
          (string) $student->user?->full_name
        ) === strtolower($value)
      );
  }

  public function classifications(AssistantActorContext $context): ?Builder
  {
    if (!$this->canViewClassSummary($context)) {
      return null;
    }

    $query = Classification::query()->where(
      'classifications.institution_id',
      $context->institution->id
    );

    if ($context->institutionUser->isAdmin()) {
      return $query;
    }

    return $query->where(function (Builder $query) use ($context) {
      $query->where('form_teacher_id', $context->userId())->orWhereIn(
        'id',
        CourseTeacher::query()
          ->where('institution_id', $context->institution->id)
          ->where('user_id', $context->userId())
          ->select('classification_id')
      );
    });
  }
}
