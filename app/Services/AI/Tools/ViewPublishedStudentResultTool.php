<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\TermResult;
use App\Services\AI\AssistantToolScope;
use Illuminate\Support\Str;

class ViewPublishedStudentResultTool implements AssistantTool
{
  public function __construct(private readonly AssistantToolScope $scope)
  {
  }

  public function name(): string
  {
    return 'view_published_student_result';
  }

  public function description(): string
  {
    return 'View a published result for a student the current actor is authorized to access.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'student' => ['type' => 'string'],
        'academic_session' => ['type' => 'string'],
        'term' => ['type' => 'string', 'enum' => ['first', 'second', 'third']]
      ],
      'required' => ['student', 'academic_session', 'term'],
      'additionalProperties' => false
    ];
  }

  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->scope->canAccessInstitution($context)) {
      return AssistantToolResult::denied();
    }

    foreach (['student', 'academic_session', 'term'] as $required) {
      $value = $arguments[$required] ?? null;

      // Tool arguments arrive from free-form conversation, so a value may
      // be any shape. Only a non-empty scalar is usable here.
      if (!is_scalar($value) || !filled($value)) {
        return AssistantToolResult::invalid(
          "The {$required} value is required."
        );
      }
    }

    $students = $this->scope->students($context);
    if (!$students) {
      return AssistantToolResult::denied();
    }

    $student = $this->resolveStudent(
      $students,
      (string) $arguments['student'],
      $context
    );
    if (!$student) {
      return AssistantToolResult::invalid(
        'The requested student was not found in your authorized scope.'
      );
    }

    $institutionUser = $context->institutionUser;
    if (!$institutionUser->canViewResultsFor($student)) {
      return AssistantToolResult::denied();
    }

    $session = AcademicSession::query()
      ->where('title', (string) $arguments['academic_session'])
      ->first();
    if (!$session) {
      return AssistantToolResult::invalid(
        'The requested academic session was not found.'
      );
    }

    $term = Str::lower((string) $arguments['term']);
    if (!in_array($term, ['first', 'second', 'third'], true)) {
      return AssistantToolResult::invalid(
        'The requested term is not supported.'
      );
    }

    $results = TermResult::query()
      ->where('institution_id', $context->institution->id)
      ->where('student_id', $student->id)
      ->where('academic_session_id', $session->id)
      ->where('term', $term)
      ->isPublished()
      ->when(!$institutionUser->isAdmin(), fn($query) => $query->activated())
      ->with(['classification', 'academicSession'])
      ->get();

    if ($results->isEmpty()) {
      return AssistantToolResult::success(
        'No published result was found for the requested context.',
        [
          'student' => $this->studentSummary($student),
          'academic_session' => $session->title,
          'term' => $term,
          'results' => []
        ]
      );
    }

    return AssistantToolResult::success('Published student result retrieved.', [
      'student' => $this->studentSummary($student),
      'academic_session' => $session->title,
      'term' => $term,
      'results' => $results
        ->map(
          fn(TermResult $result) => [
            'id' => $result->id,
            'average' => $result->average,
            'position' => $result->position,
            'class_group_position' => $result->class_group_position,
            'classification' => $result->classification?->title,
            'is_activated' => $result->isActivated()
          ]
        )
        ->all()
    ]);
  }

  private function resolveStudent(
    $students,
    string $value,
    AssistantActorContext $context
  ): ?Student {
    if (Str::lower($value) === 'me') {
      return $students->where('students.user_id', $context->userId())->first();
    }

    $query = $students->clone();
    $student = $query->where('students.code', $value)->first();
    if ($student) {
      return $student;
    }

    return $students
      ->get()
      ->first(
        fn(Student $student) => Str::lower(
          (string) $student->user?->full_name
        ) === Str::lower($value)
      );
  }

  private function studentSummary(Student $student): array
  {
    return [
      'id' => $student->id,
      'code' => $student->code,
      'name' => $student->user?->full_name,
      'class' => $student->classification?->title
    ];
  }
}
