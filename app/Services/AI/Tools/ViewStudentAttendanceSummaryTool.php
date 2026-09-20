<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Attendance;
use App\Models\Student;
use App\Services\AI\AssistantAcademicPeriodResolver;
use App\Services\AI\AssistantToolScope;
use Illuminate\Support\Carbon;

class ViewStudentAttendanceSummaryTool implements AssistantTool
{
  public function __construct(
    private readonly AssistantToolScope $scope,
    private readonly AssistantAcademicPeriodResolver $periodResolver
  ) {
  }

  public function name(): string
  {
    return 'view_student_attendance_summary';
  }

  public function description(): string
  {
    return 'Summarize attendance days and sign-outs for an authorized student during an explicit date range or academic term.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'student' => [
          'type' => 'string',
          'description' =>
            'Student code, exact full name, or me when the actor is the student.'
        ],
        'academic_session' => [
          'type' => 'string',
          'description' => 'Academic session title, for example 2025/2026.'
        ],
        'term' => [
          'type' => 'string',
          'enum' => ['first', 'second', 'third']
        ],
        'from_date' => [
          'type' => 'string',
          'description' => 'Inclusive start date in YYYY-MM-DD format.'
        ],
        'to_date' => [
          'type' => 'string',
          'description' => 'Inclusive end date in YYYY-MM-DD format.'
        ]
      ],
      'required' => ['student'],
      'additionalProperties' => false
    ];
  }

  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->scope->canViewAttendanceSummary($context)) {
      return AssistantToolResult::denied();
    }

    $studentValue = $arguments['student'] ?? null;
    if (!is_scalar($studentValue) || !filled($studentValue)) {
      return AssistantToolResult::invalid('The student value is required.');
    }

    $student = $this->scope->resolveStudent($context, (string) $studentValue);
    if (!$student) {
      return AssistantToolResult::invalid(
        'The requested student was not found in your authorized scope.'
      );
    }

    $period = $this->periodResolver->resolve($arguments, $context);
    if ($period instanceof AssistantToolResult) {
      return $period;
    }

    $attendance = Attendance::query()
      ->where('institution_id', $context->institution->id)
      ->where('institution_user_id', $student->institution_user_id)
      ->whereNotNull('signed_in_at')
      ->whereDate('signed_in_at', '>=', $period['from_date'])
      ->whereDate('signed_in_at', '<=', $period['to_date'])
      ->orderBy('signed_in_at')
      ->get(['signed_in_at', 'signed_out_at']);

    $attendanceDays = $attendance
      ->map(fn(Attendance $record) => $record->signed_in_at?->toDateString())
      ->filter()
      ->unique()
      ->values();
    $signedOutDays = $attendance
      ->map(fn(Attendance $record) => $record->signed_out_at?->toDateString())
      ->filter()
      ->unique()
      ->values();
    $expectedDays = $period['expected_days'];

    return AssistantToolResult::success(
      'Student attendance summary retrieved.',
      [
        'student' => $this->studentSummary($student),
        'period' => $this->periodSummary($period),
        'attendance_days' => $attendanceDays->count(),
        'signed_out_days' => $signedOutDays->count(),
        'records' => $attendance->count(),
        'expected_days' => $expectedDays,
        'attendance_rate' =>
          $expectedDays > 0
            ? round(($attendanceDays->count() / $expectedDays) * 100, 2)
            : null
      ]
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

  private function periodSummary(array $period): array
  {
    return [
      'from_date' => $period['from_date'],
      'to_date' => $period['to_date'],
      'academic_session' => $period['academic_session'],
      'term' => $period['term']
    ];
  }
}
