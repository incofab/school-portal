<?php

namespace App\Services\AI;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;

class AssistantToolRegistry
{
    /** @param array<int, AssistantTool> $tools */
    public function __construct(private readonly array $tools) {}

    public function get(string $name): ?AssistantTool
    {
        return collect($this->tools)->first(
            fn (AssistantTool $tool) => $tool->name() === $name
        );
    }

    public function definitions(): array
    {
        return collect($this->tools)
            ->map(fn (AssistantTool $tool) => [
                'name' => $tool->name(),
                'description' => $tool->description(),
                'input_schema' => $tool->inputSchema(),
            ])
            ->values()
            ->all();
    }

    public function execute(
        string $name,
        array $arguments,
        AssistantActorContext $context,
    ): AssistantToolResult {
        $tool = $this->get($name);

        if (! $tool) {
            return AssistantToolResult::invalid('The requested assistant capability is not available.');
        }

        return $tool->execute($arguments, $context);
    }
}
