<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Attendance;
use App\Models\Classification;
use App\Services\AI\AssistantAcademicPeriodResolver;
use App\Services\AI\AssistantToolScope;
use Illuminate\Support\Collection;

class ViewClassAttendanceSummaryTool implements AssistantTool
{
  public function __construct(
    private readonly AssistantToolScope $scope,
    private readonly AssistantAcademicPeriodResolver $periodResolver
  ) {
  }

  public function name(): string
  {
    return 'view_class_attendance_summary';
  }

  public function description(): string
  {
    return 'Summarize attendance days for students in an authorized class during an explicit date range or academic term.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'classification_id' => [
          'type' => 'integer',
          'description' => 'Authorized class identifier.'
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
      'required' => ['classification_id'],
      'additionalProperties' => false
    ];
  }

  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (
      !$this->scope->canViewAttendanceSummary($context) ||
      !$this->scope->canViewClassSummary($context)
    ) {
      return AssistantToolResult::denied();
    }

    $classificationId = $this->integer($arguments['classification_id'] ?? null);
    if (!$classificationId) {
      return AssistantToolResult::invalid(
        'The class identifier must be a positive integer.'
      );
    }

    $classifications = $this->scope->classifications($context);
    $classification = $classifications?->whereKey($classificationId)->first();
    if (!$classification) {
      return AssistantToolResult::denied(
        'The requested class is not in your authorized scope.'
      );
    }

    $period = $this->periodResolver->resolve($arguments, $context);
    if ($period instanceof AssistantToolResult) {
      return $period;
    }

    $studentQuery = $this->scope->students($context);
    if (!$studentQuery) {
      return AssistantToolResult::denied();
    }

    $studentQuery->where('students.classification_id', $classification->id);
    $studentIds = (clone $studentQuery)->pluck('institution_user_id');
    $studentCount = $studentIds->count();
    $students = (clone $studentQuery)
      ->orderBy('students.id')
      ->limit(200)
      ->get();

    $attendance = Attendance::query()
      ->where('institution_id', $context->institution->id)
      ->whereIn('institution_user_id', $studentIds)
      ->whereNotNull('signed_in_at')
      ->whereDate('signed_in_at', '>=', $period['from_date'])
      ->whereDate('signed_in_at', '<=', $period['to_date'])
      ->get(['institution_user_id', 'signed_in_at', 'signed_out_at'])
      ->groupBy('institution_user_id');

    $rows = $students
      ->map(
        fn($student) => $this->studentRow(
          $student,
          $attendance->get($student->institution_user_id, collect()),
          $period['expected_days']
        )
      )
      ->sortBy('name')
      ->values();
    $expectedDays = $period['expected_days'];
    $totalAttendanceDays = $attendance->sum(
      fn(Collection $records) => $records
        ->map(fn(Attendance $record) => $record->signed_in_at?->toDateString())
        ->filter()
        ->unique()
        ->count()
    );

    return AssistantToolResult::success(
      'Class attendance summary retrieved.',
      [
        'class' => [
          'id' => $classification->id,
          'title' => $classification->title
        ],
        'period' => [
          'from_date' => $period['from_date'],
          'to_date' => $period['to_date'],
          'academic_session' => $period['academic_session'],
          'term' => $period['term']
        ],
        'student_count' => $studentCount,
        'summarized_student_count' => $students->count(),
        'total_attendance_days' => $totalAttendanceDays,
        'expected_days_per_student' => $expectedDays,
        'students' => $rows->all()
      ],
      ['limited' => $studentCount > 200, 'limit' => 200]
    );
  }

  private function studentRow(
    $student,
    Collection $attendance,
    ?int $expectedDays
  ): array {
    $attendanceDays = $attendance
      ->map(fn(Attendance $record) => $record->signed_in_at?->toDateString())
      ->filter()
      ->unique()
      ->count();
    $signedOutDays = $attendance
      ->map(fn(Attendance $record) => $record->signed_out_at?->toDateString())
      ->filter()
      ->unique()
      ->count();

    return [
      'id' => $student->id,
      'code' => $student->code,
      'name' => $student->user?->full_name,
      'attendance_days' => $attendanceDays,
      'signed_out_days' => $signedOutDays,
      'attendance_rate' =>
        $expectedDays > 0
          ? round(($attendanceDays / $expectedDays) * 100, 2)
          : null
    ];
  }

  private function integer(mixed $value): ?int
  {
    if (
      !is_scalar($value) ||
      filter_var($value, FILTER_VALIDATE_INT) === false
    ) {
      return null;
    }

    $value = (int) $value;

    return $value > 0 ? $value : null;
  }
}
