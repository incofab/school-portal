<?php

namespace App\Services\AI;

use App\Exceptions\AssistantUnavailableException;

final class AssistantRunBudget
{
  private readonly float $startedAt;

  private int $modelCalls = 0;

  private int $toolCalls = 0;

  private int $retrievalCalls = 0;

  public function __construct()
  {
    $this->startedAt = microtime(true);
  }

  public function consumeModelCall(): void
  {
    $this->consumeModelCalls(1);
  }

  public function consumeModelCalls(int $calls): void
  {
    if ($calls < 0) {
      throw new \InvalidArgumentException(
        'Model call count cannot be negative.'
      );
    }

    if ($calls === 0) {
      return;
    }

    $this->assertWithinTime();

    if (
      $this->modelCalls + $calls >
      config('ai.assistant.max_model_calls', 3)
    ) {
      throw new AssistantUnavailableException(
        'The assistant reached its processing limit for this request.'
      );
    }

    $this->modelCalls += $calls;
  }

  public function consumeToolCall(): void
  {
    $this->assertWithinTime();
    $this->toolCalls++;

    if ($this->toolCalls > config('ai.assistant.max_tool_calls', 0)) {
      throw new AssistantUnavailableException(
        'The assistant reached its application-action limit for this request.'
      );
    }
  }

  public function consumeRetrievalCall(): void
  {
    $this->assertWithinTime();
    $this->retrievalCalls++;

    if ($this->retrievalCalls > config('ai.assistant.max_retrieval_calls', 0)) {
      throw new AssistantUnavailableException(
        'The assistant reached its retrieval limit for this request.'
      );
    }
  }

  public function toArray(): array
  {
    return [
      'model_calls' => $this->modelCalls,
      'tool_calls' => $this->toolCalls,
      'retrieval_calls' => $this->retrievalCalls
    ];
  }

  public function assertWithinTime(): void
  {
    $maxSeconds = max(
      1,
      min(300, (int) config('ai.assistant.max_turn_seconds', 75))
    );

    if (microtime(true) - $this->startedAt > $maxSeconds) {
      throw new AssistantUnavailableException(
        'The assistant reached its processing time limit for this request.'
      );
    }
  }
}
