<?php

namespace App\Support\AI;

use App\Models\AssistantRunMetric;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Aggregates assistant run telemetry for the operational diagnostics view.
 *
 * Every figure here comes from `assistant_run_metrics`, which holds no message
 * content, so diagnostics can be reviewed without reading anyone's
 * conversation.
 */
class AssistantDiagnostics
{
  public function __construct(private readonly int $days = 30)
  {
  }

  public static function forDays(int $days): self
  {
    return new self(max(1, min($days, 365)));
  }

  public function summary(): array
  {
    $runs = $this->query()->count();

    return [
      'window_days' => $this->days,
      'runs' => $runs,
      'failures' => $this->query()
        ->where('outcome', 'failed')
        ->count(),
      'failure_rate' => $this->rate($this->failureCount(), $runs),
      'grounded_rate' => $this->rate($this->groundedCount(), $runs),
      'clarification_rate' => $this->rate(
        $this->outcomeCount('clarified'),
        $runs
      ),
      'actions_prepared' => $this->outcomeCount('action_prepared'),
      'actions_rejected' => $this->outcomeCount('action_rejected'),
      'median_duration_ms' => $this->percentile('duration_ms', 0.5),
      'p95_duration_ms' => $this->percentile('duration_ms', 0.95),
      'input_tokens' => (int) $this->query()->sum('input_tokens'),
      'output_tokens' => (int) $this->query()->sum('output_tokens'),
      'avg_model_calls' => $this->average('model_calls'),
      'avg_tool_calls' => $this->average('tool_calls'),
      'avg_retrieval_calls' => $this->average('retrieval_calls')
    ];
  }

  public function feedback(): array
  {
    $rated = $this->query()->whereNotNull('feedback_rating');

    return [
      'helpful' => (clone $rated)->where('feedback_rating', 'helpful')->count(),
      'not_helpful' => (clone $rated)
        ->where('feedback_rating', 'not_helpful')
        ->count(),
      'rated' => $rated->count()
    ];
  }

  public function failuresByReason(): array
  {
    return $this->query()
      ->where('outcome', 'failed')
      ->selectRaw('failure_reason, count(*) as total')
      ->groupBy('failure_reason')
      ->orderByDesc('total')
      ->pluck('total', 'failure_reason')
      ->map(fn($total) => (int) $total)
      ->all();
  }

  public function outcomes(): array
  {
    return $this->query()
      ->selectRaw('outcome, count(*) as total')
      ->groupBy('outcome')
      ->orderByDesc('total')
      ->pluck('total', 'outcome')
      ->map(fn($total) => (int) $total)
      ->all();
  }

  public function byRole(): array
  {
    return $this->query()
      ->selectRaw(
        'coalesce(actor_role, "guest") as role, count(*) as total, avg(duration_ms) as avg_duration'
      )
      ->groupBy('role')
      ->orderByDesc('total')
      ->get()
      ->map(
        fn($row) => [
          'role' => $row->role,
          'runs' => (int) $row->total,
          'avg_duration_ms' => (int) round((float) $row->avg_duration)
        ]
      )
      ->all();
  }

  public function busiestInstitutions(int $limit = 10): array
  {
    return $this->query()
      ->whereNotNull('assistant_run_metrics.institution_id')
      ->join(
        'institutions',
        'institutions.id',
        '=',
        'assistant_run_metrics.institution_id'
      )
      ->selectRaw(
        'institutions.name as institution, count(*) as total, sum(case when outcome = "failed" then 1 else 0 end) as failures'
      )
      ->groupBy('institutions.name')
      ->orderByDesc('total')
      ->limit($limit)
      ->get()
      ->map(
        fn($row) => [
          'institution' => $row->institution,
          'runs' => (int) $row->total,
          'failures' => (int) $row->failures
        ]
      )
      ->all();
  }

  /**
   * The switches an operational owner needs in order to disable or throttle
   * the assistant, reported as currently configured.
   */
  public static function operationalControls(): array
  {
    return [
      'assistant_enabled' => (bool) config('ai.assistant.enabled', true),
      'guest_access' => (bool) config('ai.assistant.guest_access', true),
      'knowledge_enabled' => (bool) config('ai.knowledge.enabled', true),
      'telemetry_enabled' => (bool) config('ai.telemetry.enabled', true),
      'model' => config('ai.assistant.model'),
      'provider' => config('ai.assistant.provider', 'openai'),
      'fallback_provider' => config('ai.assistant.fallback_provider'),
      'retry_attempts' => (int) config('ai.assistant.retry_attempts', 1),
      'timeout_seconds' => (int) config('ai.assistant.timeout', 60),
      'max_turn_seconds' => (int) config('ai.assistant.max_turn_seconds', 75),
      'rate_limit' => (int) config('ai.assistant.rate_limit', 20),
      'rate_limit_window_minutes' => (int) config(
        'ai.assistant.rate_limit_window',
        1
      ),
      'max_model_calls' => (int) config('ai.assistant.max_model_calls', 3),
      'max_tool_calls' => (int) config('ai.assistant.max_tool_calls', 5),
      'max_retrieval_calls' => (int) config(
        'ai.assistant.max_retrieval_calls',
        3
      ),
      'max_tokens' => (int) config('ai.assistant.max_tokens', 1600),
      'max_input_chars' => (int) config('ai.assistant.max_input_chars', 4000),
      'user_budget' => (int) config('ai.assistant.user_budget', 0),
      'institution_budget' => (int) config(
        'ai.assistant.institution_budget',
        0
      ),
      'guest_budget' => (int) config('ai.assistant.guest_budget', 0),
      'usage_window_minutes' => (int) config(
        'ai.assistant.usage_window_minutes',
        1440
      ),
      'public_cache_minutes' => (int) config(
        'ai.assistant.public_cache_minutes',
        0
      )
    ];
  }

  private function query(): Builder
  {
    return AssistantRunMetric::query()->where(
      'assistant_run_metrics.created_at',
      '>=',
      Carbon::now()->subDays($this->days)
    );
  }

  private function failureCount(): int
  {
    return $this->outcomeCount('failed');
  }

  private function outcomeCount(string $outcome): int
  {
    return $this->query()
      ->where('outcome', $outcome)
      ->count();
  }

  private function groundedCount(): int
  {
    return $this->query()
      ->where('grounded', true)
      ->count();
  }

  private function rate(int $part, int $total): float
  {
    return $total === 0 ? 0.0 : round(($part / $total) * 100, 1);
  }

  private function average(string $column): float
  {
    return round((float) $this->query()->avg($column), 2);
  }

  /**
   * Percentiles are computed in PHP so the query stays portable across the
   * MySQL versions EduManager runs on.
   */
  private function percentile(string $column, float $percentile): int
  {
    $values = $this->query()
      ->orderBy($column)
      ->pluck($column)
      ->values();

    if ($values->isEmpty()) {
      return 0;
    }

    $index = (int) floor($percentile * ($values->count() - 1));

    return (int) $values[$index];
  }
}
