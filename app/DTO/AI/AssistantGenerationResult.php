<?php

namespace App\DTO\AI;

final readonly class AssistantGenerationResult
{
    public function __construct(
        public string $text,
        public array $meta = [],
    ) {}
}
