<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Enums\InstitutionUserType;
use App\Models\CourseTeacher;
use App\Models\InstitutionUser;
use App\Services\AI\AssistantToolScope;

class ListAuthorizedTeacherAssignmentsTool implements AssistantTool
{
  public function __construct(private readonly AssistantToolScope $scope)
  {
  }

  public function name(): string
  {
    return 'list_authorized_teacher_assignments';
  }

  public function description(): string
  {
    return 'List course and class assignments for the current teacher, or for active institution teachers when the actor is an administrator.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'teacher' => [
          'type' => 'string',
          'description' =>
            'Optional teacher user id or exact full name; administrators may use this filter.'
        ]
      ],
      'additionalProperties' => false
    ];
  }

  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->scope->canViewTeacherAssignments($context)) {
      return AssistantToolResult::denied();
    }

    $institutionId = $context->institution->id;
    $activeStaff = InstitutionUser::query()
      ->where('institution_id', $institutionId)
      ->where('status', 'active')
      ->where('type', InstitutionUserType::Teacher->value)
      ->with('user')
      ->get();

    $teacherValue = $arguments['teacher'] ?? null;
    $staff = $activeStaff;
    if (!$context->institutionUser->isAdmin()) {
      $staff = $activeStaff->where('user_id', $context->userId());

      if (
        filled($teacherValue) &&
        (!is_scalar($teacherValue) ||
          (string) $teacherValue !== (string) $context->userId())
      ) {
        return AssistantToolResult::denied();
      }
    } elseif (filled($teacherValue)) {
      $teacher = $this->findStaff($activeStaff, $teacherValue);
      if (!$teacher) {
        return AssistantToolResult::invalid(
          'The requested active teacher was not found.'
        );
      }
      $staff = $activeStaff->where('user_id', $teacher->user_id);
    }

    $assignments = CourseTeacher::query()
      ->where('institution_id', $institutionId)
      ->whereIn('user_id', $staff->pluck('user_id'))
      ->whereHas(
        'course',
        fn($query) => $query->where('institution_id', $institutionId)
      )
      ->whereHas(
        'classification',
        fn($query) => $query->where('institution_id', $institutionId)
      )
      ->with(['user', 'course', 'classification'])
      ->orderBy('user_id')
      ->orderBy('classification_id')
      ->orderBy('course_id')
      ->limit(201)
      ->get();
    $limited = $assignments->count() > 200;
    $assignments = $assignments->take(200);

    return AssistantToolResult::success(
      'Teacher assignments retrieved.',
      [
        'assignments' => $assignments
          ->map(
            fn(CourseTeacher $assignment) => [
              'id' => $assignment->id,
              'teacher' => [
                'id' => $assignment->user_id,
                'name' => $assignment->user?->full_name
              ],
              'class' => [
                'id' => $assignment->classification_id,
                'title' => $assignment->classification?->title
              ],
              'course' => [
                'id' => $assignment->course_id,
                'title' => $assignment->course?->title
              ]
            ]
          )
          ->values()
          ->all()
      ],
      ['limited' => $limited, 'limit' => 200]
    );
  }

  private function findStaff($staff, mixed $value): ?InstitutionUser
  {
    if (!is_scalar($value) || !filled($value)) {
      return null;
    }

    $value = trim((string) $value);
    if (ctype_digit($value)) {
      return $staff->firstWhere('user_id', (int) $value);
    }

    return $staff->first(
      fn(InstitutionUser $institutionUser) => strtolower(
        (string) $institutionUser->user?->full_name
      ) === strtolower($value)
    );
  }
}
