<?php

use App\DTO\AI\AssistantGenerationResult;
use App\DTO\AI\KnowledgeSearchResult;
use App\DTO\AI\KnowledgeSource;
use App\Exceptions\AssistantRateLimitExceededException;
use App\Models\Institution;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantResponseCache;
use App\Services\AI\AssistantUsageGuard;
use App\Services\AI\LaravelAiAssistantTextGenerator;
use App\Services\AI\LaravelAiTextAgent;
use App\Services\AI\RegisteredAssistantTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Prompts\AgentPrompt;

it('enforces configured per-user and per-institution budgets', function () {
  $institution = Institution::factory()->create();
  $user = $institution->createdBy;
  $context = app(AssistantContextResolver::class)->resolve($institution);
  $request = Request::create('/assistant/conversations/test/messages', 'POST');
  $request->setUserResolver(fn() => $user);

  config([
    'ai.assistant.user_budget' => 1,
    'ai.assistant.institution_budget' => 1,
    'ai.assistant.usage_window_minutes' => 60
  ]);

  $guard = app(AssistantUsageGuard::class);
  $guard->check($request, $context);

  expect(fn() => $guard->check($request, $context))->toThrow(
    AssistantRateLimitExceededException::class
  );
});
it('caches only the public knowledge-grounded generation', function () {
  Cache::spy();
  config(['ai.assistant.public_cache_minutes' => 10]);
  $knowledge = new KnowledgeSearchResult([
    new KnowledgeSource(
      id: 'faq:1',
      slug: 'faq-login',
      title: 'Login help',
      excerpt: 'Use the login page.',
      score: 4.0,
    )
  ]);
  $calls = 0;
  $cache = app(AssistantResponseCache::class);

  $first = $cache->remember('How do I log in?', $knowledge, function () use (
    &$calls
  ) {
    $calls++;

    return new AssistantGenerationResult('First answer', [
      'provider' => 'test'
    ]);
  });
  $second = $cache->remember('How do I log in?', $knowledge, function () use (
    &$calls
  ) {
    $calls++;

    return new AssistantGenerationResult('Second answer');
  });

  expect($calls)
    ->toBe(1)
    ->and($first->text)
    ->toBe('First answer')
    ->and($second->text)
    ->toBe('First answer')
    ->and($second->meta['cache_hit'])
    ->toBeTrue();
});

it(
  'passes the configured output-token ceiling to the provider request',
  function () {
    config([
      'ai.assistant.max_tokens' => 777,
      'ai.assistant.retry_attempts' => 1
    ]);
    LaravelAiTextAgent::fake(['Bounded answer']);

    app(LaravelAiAssistantTextGenerator::class)->generate('System', 'Prompt');

    LaravelAiTextAgent::assertPrompted(function (AgentPrompt $request): bool {
      expect($request->agent->maxTokens())->toBe(777);

      return true;
    });
  }
);

it('passes bounded native tools and model steps to Laravel AI', function () {
  config([
    'ai.assistant.max_tokens' => 777,
    'ai.assistant.retry_attempts' => 1,
    'ai.assistant.max_turn_seconds' => 75
  ]);
  LaravelAiTextAgent::fake(['Native tool answer']);
  $tool = new RegisteredAssistantTool(
    'echo',
    'Echo a value for testing.',
    [
      'type' => 'object',
      'properties' => ['value' => ['type' => 'string']],
      'required' => ['value']
    ],
    fn(array $arguments) => $arguments['value']
  );

  $result = app(LaravelAiAssistantTextGenerator::class)->generateWithTools(
    'System',
    'Prompt',
    [$tool],
    3
  );

  LaravelAiTextAgent::assertPrompted(function (AgentPrompt $request): bool {
    expect($request->agent->maxSteps())
      ->toBe(3)
      ->and($request->agent->maxTokens())
      ->toBe(777)
      ->and($request->agent->tools())
      ->toHaveCount(1)
      ->and($request->agent->tools()[0]->name())
      ->toBe('echo');

    return true;
  });

  expect($result->text)
    ->toBe('Native tool answer')
    ->and($result->meta['model_calls'])
    ->toBe(1);
});

it('keeps provider retries within the configured turn budget', function () {
  config([
    'ai.assistant.max_turn_seconds' => 75,
    'ai.assistant.timeout' => 300,
    'ai.assistant.retry_attempts' => 3,
    'ai.assistant.retry_delay_ms' => 1000,
    'ai.assistant.fallback_provider' => 'anthropic',
    'ai.assistant.fallback_model' => 'claude-test'
  ]);
  LaravelAiTextAgent::fake(['Bounded retry answer']);

  app(LaravelAiAssistantTextGenerator::class)->generate('System', 'Prompt');

  LaravelAiTextAgent::assertPrompted(function (AgentPrompt $request): bool {
    expect($request->timeout)->toBe(11);

    return true;
  });
});
