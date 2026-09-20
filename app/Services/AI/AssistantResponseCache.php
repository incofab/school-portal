<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantGenerationResult;
use App\DTO\AI\KnowledgeSearchResult;
use Illuminate\Support\Facades\Cache;

/**
* Caches only public, knowledge-grounded generations. Private actor context,
* application observations, and action results never enter this cache.
*/
class AssistantResponseCache
{
    public function remember(
        string $message,
        KnowledgeSearchResult $knowledge,
        callable $generate
    ): AssistantGenerationResult {
        $ttl = (int) config('ai.assistant.public_cache_minutes', 0);

        if ($ttl <= 0 || $knowledge->isEmpty()) {
            return $generate();
        }

        $key = 'ai-assistant:public-answer:'.hash(
            'sha256',
            json_encode([
                'message' => mb_strtolower(trim($message)),
                'model' => config('ai.assistant.model'),
                'provider' => config('ai.assistant.provider'),
                'fallback_provider' => config('ai.assistant.fallback_provider'),
                'fallback_model' => config('ai.assistant.fallback_model'),
                'sources' => $knowledge->toArray(),
            ], JSON_THROW_ON_ERROR)
        );
        $cached = Cache::get($key);

        if (is_array($cached) && isset($cached['text'])) {
            return new AssistantGenerationResult(
                text: (string) $cached['text'],
                meta: [
                    ...(array) ($cached['meta'] ?? []),
                    'cache_hit' => true,
                ]
            );
        }

        $result = $generate();
        Cache::put($key, [
            'text' => $result->text,
            'meta' => $result->meta,
        ], now()->addMinutes($ttl));

        return $result;
    }
}
