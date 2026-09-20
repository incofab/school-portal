<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\TermResult;
use App\Services\AI\AssistantToolScope;

class ViewClassPerformanceSummaryTool implements AssistantTool
{
  public function __construct(private readonly AssistantToolScope $scope)
  {
  }

  public function name(): string
  {
    return 'view_class_performance_summary';
  }

  public function description(): string
  {
    return 'Summarize published academic performance for an authorized class, academic session, and term.';
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
        'for_mid_term' => [
          'type' => 'boolean',
          'description' => 'Whether to use the mid-term result set.'
        ]
      ],
      'required' => ['classification_id', 'academic_session', 'term'],
      'additionalProperties' => false
    ];
  }

  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->scope->canViewClassSummary($context)) {
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

    $sessionValue = $arguments['academic_session'] ?? null;
    if (!is_scalar($sessionValue) || !filled($sessionValue)) {
      return AssistantToolResult::invalid(
        'The academic session value is required.'
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

    $termValue = $arguments['term'] ?? null;
    if (
      !is_scalar($termValue) ||
      !in_array(
        strtolower((string) $termValue),
        array_map(fn(TermType $term) => $term->value, TermType::cases()),
        true
      )
    ) {
      return AssistantToolResult::invalid(
        'The requested term is not supported.'
      );
    }
    $term = strtolower((string) $termValue);

    $forMidTerm = $arguments['for_mid_term'] ?? false;
    if (!is_bool($forMidTerm)) {
      return AssistantToolResult::invalid(
        'The mid-term flag must be a boolean value.'
      );
    }

    $query = TermResult::query()
      ->where('institution_id', $context->institution->id)
      ->where('classification_id', $classification->id)
      ->where('academic_session_id', $session->id)
      ->where('term', $term)
      ->where('for_mid_term', $forMidTerm)
      ->where(function ($query) use ($forMidTerm) {
        if ($forMidTerm) {
          $query->where('for_mid_term', true);
        } else {
          $query->whereNotNull('result_publication_id');
        }
      });

    if (!$context->institutionUser->isAdmin()) {
      $query->activated();
    }

    $totalResults = (clone $query)->count();
    $results = $query
      ->with('student.user')
      ->orderBy('position')
      ->orderBy('student_id')
      ->limit(200)
      ->get();
    $averages = $results
      ->pluck('average')
      ->filter(fn($average) => $average !== null)
      ->map(fn($average) => (float) $average);

    return AssistantToolResult::success(
      $results->isEmpty()
        ? 'No published result was found for the requested class context.'
        : 'Class performance summary retrieved.',
      [
        'class' => [
          'id' => $classification->id,
          'title' => $classification->title
        ],
        'academic_session' => $session->title,
        'term' => $term,
        'for_mid_term' => $forMidTerm,
        'students_with_results' => $totalResults,
        'class_average' => $averages->isEmpty()
          ? null
          : round($averages->average(), 2),
        'highest_average' => $averages->max(),
        'lowest_average' => $averages->min(),
        'students' => $results
          ->map(
            fn(TermResult $result) => [
              'id' => $result->student_id,
              'code' => $result->student?->code,
              'name' => $result->student?->user?->full_name,
              'average' => $result->average,
              'position' => $result->position
            ]
          )
          ->values()
          ->all()
      ],
      ['limited' => $totalResults > 200, 'limit' => 200]
    );
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
