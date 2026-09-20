<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Enums\PaymentInterval;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Fee;
use App\Models\Receipt;
use App\Models\Student;
use App\Services\AI\AssistantToolScope;

class ViewStudentFeeSummaryTool implements AssistantTool
{
  public function __construct(private readonly AssistantToolScope $scope)
  {
  }

  public function name(): string
  {
    return 'view_student_fee_summary';
  }

  public function description(): string
  {
    return 'Summarize applicable fees, amounts paid, and balances for an authorized student, optionally for an academic session and term.';
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
    if (!$this->scope->canViewFeeSummary($context)) {
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

    $period = $this->resolvePeriod($arguments);
    if ($period instanceof AssistantToolResult) {
      return $period;
    }

    $fees = Fee::query()
      ->where('institution_id', $context->institution->id)
      ->with(['feeCategories', 'academicSession'])
      ->get()
      ->filter(
        fn(Fee $fee) => $fee->forStudent($student, $student->classification) &&
          $this->matchesPeriod($fee, $period)
      )
      ->values();

    $receipts = Receipt::query()
      ->where('institution_id', $context->institution->id)
      ->where('user_id', $student->user_id)
      ->whereIn('fee_id', $fees->pluck('id'))
      ->get()
      ->filter(
        fn(Receipt $receipt) => $this->matchesReceiptPeriod($receipt, $period)
      )
      ->sortByDesc('id')
      ->groupBy('fee_id')
      ->map(fn($items) => $items->first());

    $rows = $fees
      ->map(function (Fee $fee) use ($receipts): array {
        $receipt = $receipts->get($fee->id);
        $amountPaid = $receipt ? (float) $receipt->amount_paid : 0.0;
        $amountRemaining = $receipt
          ? (float) $receipt->amount_remaining
          : (float) $fee->amount;

        return [
          'id' => $fee->id,
          'title' => $fee->title,
          'amount' => (float) $fee->amount,
          'amount_paid' => $amountPaid,
          'amount_remaining' => max(0, $amountRemaining),
          'payment_interval' => $fee->payment_interval?->value,
          'term' => $fee->term?->value,
          'academic_session' => $fee->academicSession?->title,
          'status' => $receipt?->status?->value ?? 'unpaid'
        ];
      })
      ->values();

    return AssistantToolResult::success('Student fee summary retrieved.', [
      'student' => $this->studentSummary($student),
      'period' => [
        'academic_session' => $period['academic_session'],
        'term' => $period['term']
      ],
      'total_amount_due' => $rows->sum('amount'),
      'total_amount_paid' => $rows->sum('amount_paid'),
      'total_amount_remaining' => $rows->sum('amount_remaining'),
      'fees' => $rows->all()
    ]);
  }

  private function resolvePeriod(array $arguments): array|AssistantToolResult
  {
    $sessionValue = $arguments['academic_session'] ?? null;
    $termValue = $arguments['term'] ?? null;
    $hasSession = filled($sessionValue);
    $hasTerm = filled($termValue);

    if (!$hasSession && !$hasTerm) {
      return [
        'academic_session' => null,
        'academic_session_id' => null,
        'term' => null
      ];
    }

    if (
      !$hasSession ||
      !$hasTerm ||
      !is_scalar($sessionValue) ||
      !is_scalar($termValue)
    ) {
      return AssistantToolResult::invalid(
        'Provide both the academic session and term when filtering fees.'
      );
    }

    $session = AcademicSession::query()
      ->where('title', (string) $sessionValue)
      ->first();
    if (!$session) {
      return AssistantToolResult::invalid(
        'The requested academic session was not found.'
      );
    }

    $term = strtolower((string) $termValue);
    if (
      !in_array(
        $term,
        array_map(
          fn(TermType $termType) => $termType->value,
          TermType::cases()
        ),
        true
      )
    ) {
      return AssistantToolResult::invalid(
        'The requested term is not supported.'
      );
    }

    return [
      'academic_session' => $session->title,
      'academic_session_id' => $session->id,
      'term' => $term
    ];
  }

  private function matchesPeriod(Fee $fee, array $period): bool
  {
    if (!$period['academic_session_id']) {
      return true;
    }

    return match ($fee->payment_interval) {
      PaymentInterval::OneTime => true,
      PaymentInterval::Sessional => $fee->academic_session_id ===
        $period['academic_session_id'],
      PaymentInterval::Termly => $fee->academic_session_id ===
        $period['academic_session_id'] &&
        $fee->term?->value === $period['term'],
      default => false
    };
  }

  private function matchesReceiptPeriod(Receipt $receipt, array $period): bool
  {
    if (!$period['academic_session_id']) {
      return true;
    }

    return ($receipt->academic_session_id === null ||
      $receipt->academic_session_id === $period['academic_session_id']) &&
      ($receipt->term === null || $receipt->term?->value === $period['term']);
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
