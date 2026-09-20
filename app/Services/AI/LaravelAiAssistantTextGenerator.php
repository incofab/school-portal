<?php

namespace App\Services\AI;

use App\Contracts\AI\AssistantTextGenerator;
use App\Contracts\AI\AssistantToolCallingTextGenerator;
use App\DTO\AI\AssistantGenerationResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\FailoverableException;
use Throwable;

class LaravelAiAssistantTextGenerator implements
  AssistantTextGenerator,
  AssistantToolCallingTextGenerator
{
  public function generate(
    string $systemPrompt,
    string $prompt,
    array $options = []
  ): AssistantGenerationResult {
    return $this->generateRequest($systemPrompt, $prompt, [], 1, $options);
  }

  public function generateWithTools(
    string $systemPrompt,
    string $prompt,
    array $tools,
    int $maxSteps
  ): AssistantGenerationResult {
    return $this->generateRequest(
      $systemPrompt,
      $prompt,
      $tools,
      max(1, min(10, $maxSteps))
    );
  }

  /**
   * @param  array<int, object>  $tools
   */
  private function generateRequest(
    string $systemPrompt,
    string $prompt,
    array $tools = [],
    int $maxSteps = 1,
    array $options = []
  ): AssistantGenerationResult {
    $providers = $this->providers($options);
    $retryAttempts = max(
      1,
      min(
        3,
        (int) ($options['retry_attempts'] ??
          config('ai.assistant.retry_attempts', 1))
      )
    );
    $timeout = max(
      1,
      min(
        300,
        (int) ($options['timeout'] ?? config('ai.assistant.timeout', 60))
      )
    );
    $retryDelayMs = max(
      0,
      min(
        5000,
        (int) ($options['retry_delay_ms'] ??
          config('ai.assistant.retry_delay_ms', 250))
      )
    );
    $maxTokens = array_key_exists('max_tokens', $options)
      ? $options['max_tokens']
      : max(1, (int) config('ai.assistant.max_tokens', 1600));

    // Keep the sum of provider request timeouts within the turn budget when
    // retries are enabled. The run budget also checks between application
    // stages, but it cannot interrupt an in-flight HTTP request.
    $maxTurnSeconds = max(
      1,
      min(
        300,
        (int) ($options['max_turn_seconds'] ??
          config('ai.assistant.max_turn_seconds', 75))
      )
    );
    $maxProviderAttempts = max(1, count($providers) * $retryAttempts * $maxSteps);
    $maxRetryDelays = count($providers) * max(0, $retryAttempts - 1);
    $availableRequestMs = max(
      1000,
      $maxTurnSeconds * 1000 - $retryDelayMs * $maxRetryDelays
    );
    $timeout = min(
      $timeout,
      max(1, (int) floor($availableRequestMs / $maxProviderAttempts / 1000))
    );

    $providerSelection = count($providers) === 1
      ? $providers[0][0]
      : collect($providers)
        ->mapWithKeys(fn(array $pair) => [$pair[0] => $pair[1]])
        ->all();
    $lastException = null;

    for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
      try {
        $agent = new LaravelAiTextAgent(
          $systemPrompt,
          $tools,
          $maxSteps,
          $maxTokens === null ? null : max(1, (int) $maxTokens)
        );
        $response = $agent->prompt(
          $prompt,
          provider: $providerSelection,
          model: count($providers) === 1 ? $providers[0][1] : null,
          timeout: $timeout
        );

        $actualProvider = $response->meta->provider;
        $fallbackUsed = $actualProvider !== $providers[0][0];

        if ($fallbackUsed) {
          Log::warning('EduManager AI assistant provider failed over.', [
            'provider' => $providers[0][0],
            'fallback_provider' => $actualProvider,
            'exception' => null
          ]);
        }

        return new AssistantGenerationResult(
          text: trim($response->text),
          meta: [
            'provider' => $actualProvider,
            'model' => $response->meta->model,
            'input_tokens' => $response->usage->promptTokens,
            'output_tokens' => $response->usage->completionTokens,
            'model_calls' => max(1, $response->steps->count()),
            'provider_attempt' => $attempt,
            'fallback_used' => $fallbackUsed
          ]
        );
      } catch (Throwable $exception) {
        $lastException = $exception;

        if ($attempt < $retryAttempts && $this->isRetryable($exception)) {
          usleep($retryDelayMs * 1000);
          continue;
        }

        if (!$this->isRetryable($exception)) {
          break;
        }
      }
    }

    if (
      $lastException &&
      count($providers) > 1 &&
      $this->isRetryable($lastException)
    ) {
      Log::warning('EduManager AI assistant providers failed.', [
        'provider' => $providers[0][0],
        'fallback_provider' => $providers[1][0],
        'exception' => $lastException::class
      ]);
    }

    throw $lastException ?? new \RuntimeException('No AI provider is configured.');
  }

  /**
   * @return array<int, array{0: string, 1: string|null}>
   */
  private function providers(array $options): array
  {
    $primary = [
      (string) ($options['provider'] ?? config('ai.assistant.provider', 'openai')),
      $options['model'] ?? config('ai.assistant.model', 'gpt-5.4-nano')
    ];
    $fallbackProvider = array_key_exists('fallback_provider', $options)
      ? $options['fallback_provider']
      : config('ai.assistant.fallback_provider');

    if (!$fallbackProvider) {
      return [$primary];
    }

    return [
      $primary,
      [
        (string) $fallbackProvider,
        $options['fallback_model'] ??
          config('ai.assistant.fallback_model') ??
          $primary[1]
      ]
    ];
  }

  private function isRetryable(Throwable $exception): bool
  {
    if (
      $exception instanceof FailoverableException ||
      $exception instanceof ConnectionException
    ) {
      return true;
    }

    if ($exception instanceof RequestException) {
      $status = $exception->response?->status();

      return $status === null ||
        $status === 408 ||
        $status === 425 ||
        $status === 429 ||
        $status >= 500;
    }

    return false;
  }
}
