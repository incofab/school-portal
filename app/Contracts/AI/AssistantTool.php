<?php

namespace App\Contracts\AI;

use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;

interface AssistantTool
{
    public function name(): string;

    public function description(): string;

    public function inputSchema(): array;

    public function execute(array $arguments, AssistantActorContext $context): AssistantToolResult;
}
