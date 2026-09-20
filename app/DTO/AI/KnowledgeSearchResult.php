<?php

namespace App\DTO\AI;

use Illuminate\Support\Collection;

final readonly class KnowledgeSearchResult
{
    /**
     * @param  array<int, KnowledgeSource>  $sources
     */
    public function __construct(public array $sources = []) {}

    public function isEmpty(): bool
    {
        return $this->sources === [];
    }

    public function toArray(): array
    {
        return Collection::make($this->sources)
            ->map(fn (KnowledgeSource $source) => $source->toArray())
            ->values()
            ->all();
    }
}
