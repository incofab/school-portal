<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\TermDetail;
use Illuminate\Support\Carbon;

final class AssistantAcademicPeriodResolver
{
  public function resolve(
    array $arguments,
    AssistantActorContext $context,
    bool $required = true
  ): array|AssistantToolResult {
    $fromDate = $this->date($arguments['from_date'] ?? null);
    $toDate = $this->date($arguments['to_date'] ?? null);
    $hasFromDate =
      array_key_exists('from_date', $arguments) &&
      filled($arguments['from_date']);
    $hasToDate =
      array_key_exists('to_date', $arguments) && filled($arguments['to_date']);

    $sessionValue = $arguments['academic_session'] ?? null;
    $termValue = $arguments['term'] ?? null;
    $hasSession =
      array_key_exists('academic_session', $arguments) && filled($sessionValue);
    $hasTerm = array_key_exists('term', $arguments) && filled($termValue);

    if (!$hasFromDate && !$hasToDate && !$hasSession && !$hasTerm) {
      return $required
        ? AssistantToolResult::invalid(
          'Provide a date range or an academic session and term.'
        )
        : [];
    }

    if ($hasFromDate !== $hasToDate || $hasSession !== $hasTerm) {
      return AssistantToolResult::invalid(
        'Provide both dates, or provide both the academic session and term.'
      );
    }

    if ($hasFromDate && (!$fromDate || !$toDate)) {
      return AssistantToolResult::invalid(
        'Dates must use the YYYY-MM-DD format.'
      );
    }

    if ($hasFromDate && $fromDate->gt($toDate)) {
      return AssistantToolResult::invalid(
        'The start date cannot be after the end date.'
      );
    }

    if ($hasFromDate) {
      return [
        'from' => $fromDate,
        'to' => $toDate,
        'from_date' => $fromDate->toDateString(),
        'to_date' => $toDate->toDateString(),
        'academic_session' => null,
        'academic_session_id' => null,
        'term' => null,
        'term_detail' => null,
        'expected_days' => null
      ];
    }

    if (!is_scalar($sessionValue) || !is_scalar($termValue)) {
      return AssistantToolResult::invalid(
        'The academic session and term must be scalar values.'
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

    $termDetail = TermDetail::query()
      ->where('institution_id', $context->institution->id)
      ->where('academic_session_id', $session->id)
      ->where('term', $term)
      ->where('for_mid_term', false)
      ->first();
    if (!$termDetail?->start_date || !$termDetail?->end_date) {
      return AssistantToolResult::invalid(
        'The institution has not configured dates for that academic term.'
      );
    }

    return [
      'from' => $termDetail->start_date->copy()->startOfDay(),
      'to' => $termDetail->end_date->copy()->endOfDay(),
      'from_date' => $termDetail->start_date->toDateString(),
      'to_date' => $termDetail->end_date->toDateString(),
      'academic_session' => $session->title,
      'academic_session_id' => $session->id,
      'term' => $term,
      'term_detail' => $termDetail,
      'expected_days' => $this->expectedDays($termDetail)
    ];
  }

  private function date(mixed $value): ?Carbon
  {
    if (!is_string($value) || !Carbon::hasFormat($value, 'Y-m-d')) {
      return null;
    }

    try {
      return Carbon::createFromFormat('!Y-m-d', $value);
    } catch (\Throwable) {
      return null;
    }
  }

  private function expectedDays(TermDetail $termDetail): int
  {
    if ($termDetail->expected_attendance_count !== null) {
      return max(0, $termDetail->expected_attendance_count);
    }

    $days = 0;
    $cursor = $termDetail->start_date->copy();
    while ($cursor->lte($termDetail->end_date)) {
      if ($termDetail->isActiveOnDate($cursor)) {
        $days++;
      }
      $cursor->addDay();
    }

    return $days;
  }
}
