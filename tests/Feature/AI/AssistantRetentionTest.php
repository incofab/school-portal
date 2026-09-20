<?php

use App\Models\AiConversation;
use App\Models\AssistantRunMetric;
use App\Services\AI\AssistantContextResolver;
use App\Services\AI\AssistantConversationService;

use function Pest\Laravel\artisan;

it('prunes expired conversations and telemetry', function () {
    config([
        'ai.assistant.conversation_retention_days' => 30,
        'ai.telemetry.retention_days' => 30,
    ]);

    $service = app(AssistantConversationService::class);
    $context = app(AssistantContextResolver::class)->resolve();
    $expiredConversation = $service->create($context, null);
    $expiredMessage = $service->recordMessage(
        $expiredConversation,
        'user',
        'This message should be removed.',
        $context
    );
    $expiredMessage->forceFill([
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDays(60),
    ])->save();
    $expiredConversation->forceFill([
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDays(60),
    ])->save();

    $recentConversation = $service->create($context, null);
    $oldMetric = AssistantRunMetric::query()->create([
        'conversation_id' => $expiredConversation->getKey(),
        'outcome' => 'answered',
    ]);
    $oldMetric->forceFill(['created_at' => now()->subDays(60)])->save();
    $recentMetric = AssistantRunMetric::query()->create([
        'conversation_id' => $recentConversation->getKey(),
        'outcome' => 'answered',
    ]);

    artisan('ai:prune-data', [
        '--conversation-days' => 30,
        '--telemetry-days' => 30,
    ])->assertSuccessful();

    expect(AiConversation::query()->find($expiredConversation->getKey()))
        ->toBeNull()
        ->and(
            $expiredConversation->messages()->count()
        )->toBe(0)
        ->and(AiConversation::query()->find($recentConversation->getKey()))
        ->not->toBeNull()
        ->and(AssistantRunMetric::query()->find($oldMetric->getKey()))
        ->toBeNull()
        ->and(AssistantRunMetric::query()->find($recentMetric->getKey()))
        ->not->toBeNull();
});

it('does not remove expired records in dry-run mode', function () {
    config([
        'ai.assistant.conversation_retention_days' => 30,
        'ai.telemetry.retention_days' => 30,
    ]);

    $conversation = app(AssistantConversationService::class)->create(
        app(AssistantContextResolver::class)->resolve(),
        null
    );
    $conversation->forceFill([
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDays(60),
    ])->save();

    artisan('ai:prune-data', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Conversations: 1');

    expect(AiConversation::query()->find($conversation->getKey()))
        ->not->toBeNull();
});
