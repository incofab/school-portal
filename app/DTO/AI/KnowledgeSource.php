<?php

namespace App\DTO\AI;

final readonly class KnowledgeSource
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $title,
        public string $excerpt,
        public float $score,
        public ?string $url = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'score' => $this->score,
            'url' => $this->url,
        ];
    }
}
