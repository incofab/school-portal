<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\Exceptions\AssistantRateLimitExceededException;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

/**
 * Applies optional rolling request budgets in addition to the route throttle.
 * The separate user and institution keys prevent one busy actor from
 * exhausting the whole institution's allowance unnoticed.
 */
class AssistantUsageGuard
{
  public function __construct(private readonly RateLimiter $limiter)
  {
  }

  public function check(Request $request, AssistantActorContext $context): void
  {
    $decaySeconds = max(
      60,
      (int) config('ai.assistant.usage_window_minutes', 1440) * 60
    );
    $limits = $this->limits($request, $context);

    foreach ($limits as $key => $limit) {
      if ($limit <= 0) {
        continue;
      }

      // Increment and inspect the returned count as one rate-limiter
      // operation. A separate tooManyAttempts/hit pair allows
      // concurrent requests to pass the same budget check.
      if ($this->limiter->hit($key, $decaySeconds) > $limit) {
        throw new AssistantRateLimitExceededException(
          max(1, $this->limiter->availableIn($key))
        );
      }
    }
  }

  /**
   * @return array<string, int>
   */
  private function limits(
    Request $request,
    AssistantActorContext $context
  ): array {
    $limits = [];

    if ($context->userId() !== null) {
      $limits['ai-assistant:usage:user:' . $context->userId()] = (int) config(
        'ai.assistant.user_budget',
        0
      );
    } elseif ($request->ip()) {
      $limits['ai-assistant:usage:guest:' . $request->ip()] = (int) config(
        'ai.assistant.guest_budget',
        0
      );
    }

    if ($context->institutionId() !== null) {
      $limits[
        'ai-assistant:usage:institution:' . $context->institutionId()
      ] = (int) config('ai.assistant.institution_budget', 0);
    }

    return $limits;
  }
}
