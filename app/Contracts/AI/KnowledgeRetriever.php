<?php

namespace App\Contracts\AI;

use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\KnowledgeSearchResult;

interface KnowledgeRetriever
{
    public function search(string $query, AssistantActorContext $context): KnowledgeSearchResult;
}
