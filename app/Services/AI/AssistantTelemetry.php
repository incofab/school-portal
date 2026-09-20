<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\Models\AiConversation;
use App\Models\AssistantRunMetric;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Records the shape of each assistant turn: latency, call counts, token usage,
 * groundedness, and failure category.
 *
 * Telemetry must never become a second copy of the conversation, so no message
 * content, retrieved excerpt, or tool payload is stored here — only counts,
 * identifiers, and tool names. Recording is best-effort: a telemetry failure
 * must never break a user's turn.
 */
class AssistantTelemetry
{
  public const OUTCOME_ANSWERED = 'answered';

  public const OUTCOME_CLARIFIED = 'clarified';

  public const OUTCOME_ACTION_PREPARED = 'action_prepared';

  public const OUTCOME_ACTION_REJECTED = 'action_rejected';

  public const OUTCOME_FAILED = 'failed';

  private float $startedAt;

  public function __construct()
  {
    $this->startedAt = microtime(true);
  }

  public function start(): static
  {
    $this->startedAt = microtime(true);

    return $this;
  }

  public function record(
    AiConversation $conversation,
    AssistantActorContext $context,
    string $outcome,
    array $attributes = []
  ): ?AssistantRunMetric {
    if (!config('ai.telemetry.enabled', true)) {
      return null;
    }

    try {
      return AssistantRunMetric::query()->create([
        'conversation_id' => $conversation->getKey(),
        'message_id' => $attributes['message_id'] ?? null,
        'institution_id' => $context->institutionId(),
        'user_id' => $context->userId(),
        'actor_role' => $context->role(),
        'is_guest' => $context->isGuest,
        'outcome' => $outcome,
        'failure_reason' => $attributes['failure_reason'] ?? null,
        'duration_ms' => (int) round(
          (microtime(true) - $this->startedAt) * 1000
        ),
        'model_calls' => $attributes['model_calls'] ?? 0,
        'tool_calls' => $attributes['tool_calls'] ?? 0,
        'retrieval_calls' => $attributes['retrieval_calls'] ?? 0,
        'input_tokens' => $attributes['input_tokens'] ?? null,
        'output_tokens' => $attributes['output_tokens'] ?? null,
        'grounded' => (bool) ($attributes['grounded'] ?? false),
        'source_count' => $attributes['source_count'] ?? 0,
        'tools_used' => $attributes['tools_used'] ?? []
      ]);
    } catch (Throwable $exception) {
      Log::warning('EduManager AI assistant telemetry failed.', [
        'conversation_id' => $conversation->getKey(),
        'exception' => $exception::class
      ]);

      return null;
    }
  }

  /**
   * Attach a viewer's rating to the run that produced the rated answer, so
   * feedback can be reported next to cost, latency, and groundedness.
   */
  public function recordFeedback(string $messageId, string $rating): void
  {
    try {
      AssistantRunMetric::query()
        ->where('message_id', $messageId)
        ->update(['feedback_rating' => $rating]);
    } catch (Throwable $exception) {
      Log::warning('EduManager AI assistant feedback telemetry failed.', [
        'exception' => $exception::class
      ]);
    }
  }

  /**
   * Map a provider or budget failure onto a stable, non-sensitive category.
   */
  public static function failureCategory(Throwable $exception): string
  {
    $message = $exception->getMessage();

    return match (true) {
      str_contains($message, 'processing limit') => 'model_call_budget',
      str_contains($message, 'processing time limit') => 'turn_time_budget',
      str_contains($message, 'application-action limit') => 'tool_call_budget',
      str_contains($message, 'retrieval limit') => 'retrieval_budget',
      str_contains($message, 'currently unavailable') => 'assistant_disabled',
      str_contains($message, 'temporarily unavailable') => 'provider_error',
      str_contains($message, 'could not produce') => 'empty_response',
      default => 'unknown'
    };
  }
}
