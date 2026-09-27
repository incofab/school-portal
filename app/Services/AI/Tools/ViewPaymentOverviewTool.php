<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Receipt;
use App\Services\AI\AssistantToolScope;

class ViewPaymentOverviewTool implements AssistantTool
{
  public function __construct(private readonly AssistantToolScope $scope)
  {
  }

  public function name(): string
  {
    return 'view_payment_overview';
  }

  public function description(): string
  {
    return 'Summarize fee receipts and list students with paid, owing, or all payment records for the current institution.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'status' => [
          'type' => 'string',
          'enum' => ['all', 'paid', 'owing'],
          'description' =>
            'Filter by fully paid receipts or receipts with a remaining balance.'
        ],
        'limit' => [
          'type' => 'integer',
          'description' =>
            'Maximum number of payment rows to return, from 1 to 200.'
        ]
      ],
      'additionalProperties' => false
    ];
  }

  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->scope->canViewPaymentCollection($context)) {
      return AssistantToolResult::denied();
    }

    $status = strtolower((string) ($arguments['status'] ?? 'all'));
    if (!in_array($status, ['all', 'paid', 'owing'], true)) {
      return AssistantToolResult::invalid(
        'The payment status must be all, paid, or owing.'
      );
    }

    $limit = (int) ($arguments['limit'] ?? 100);
    if ($limit < 1 || $limit > 200) {
      return AssistantToolResult::invalid(
        'The payment row limit must be between 1 and 200.'
      );
    }

    $query = Receipt::query()
      ->where('institution_id', $context->institution->id)
      ->with(['user', 'fee', 'academicSession'])
      ->when(
        $status === 'paid',
        fn($query) => $query->where('amount_remaining', '<', 1)
      )
      ->when(
        $status === 'owing',
        fn($query) => $query->where('amount_remaining', '>=', 1)
      )
      ->orderByDesc('id');

    $total = (clone $query)->count();
    $studentCount = (clone $query)->distinct('user_id')->count('user_id');
    $totals = (clone $query)
      ->reorder()
      ->selectRaw(
        'COALESCE(SUM(amount), 0) as amount, COALESCE(SUM(amount_paid), 0) as amount_paid, COALESCE(SUM(amount_remaining), 0) as amount_remaining'
      )
      ->first();
    $receipts = $query->limit($limit)->get();
    $studentScope = $this->scope->students($context);
    if (!$studentScope) {
      return AssistantToolResult::denied();
    }

    $students = $studentScope
      ->whereIn('students.user_id', $receipts->pluck('user_id')->unique())
      ->with(['user', 'classification'])
      ->get()
      ->keyBy('user_id');

    $rows = $receipts
      ->map(function (Receipt $receipt) use ($students): array {
        $student = $students->get($receipt->user_id);

        return [
          'id' => $receipt->id,
          'student' => [
            'id' => $student?->id,
            'code' => $student?->code,
            'name' => $receipt->user?->full_name,
            'class' => $student?->classification?->title
          ],
          'fee' => $receipt->fee?->title,
          'academic_session' => $receipt->academicSession?->title,
          'term' => $receipt->term?->value,
          'amount' => (float) $receipt->amount,
          'amount_paid' => (float) $receipt->amount_paid,
          'amount_remaining' => max(0, (float) $receipt->amount_remaining),
          'status' => (float) $receipt->amount_remaining < 1 ? 'paid' : 'owing'
        ];
      })
      ->values();

    return AssistantToolResult::success(
      $total === 0
        ? 'No payment records matched the requested filter.'
        : 'Payment overview retrieved.',
      [
        'filter' => $status,
        'payment_count' => $total,
        'student_count' => $studentCount,
        'total_amount' => (float) $totals->amount,
        'total_amount_paid' => (float) $totals->amount_paid,
        'total_amount_remaining' => (float) $totals->amount_remaining,
        'students' => $rows
          ->pluck('student')
          ->unique('id')
          ->values()
          ->all(),
        'payments' => $rows->all()
      ],
      ['limited' => $total > $limit, 'limit' => $limit]
    );
  }
}
