<?php

namespace App\Services\AI;

use App\Contracts\AI\AssistantActionTool;

class AssistantActionRegistry
{
  /** @param array<int, AssistantActionTool> $tools */
  public function __construct(private readonly array $tools)
  {
  }

  public function get(string $name): ?AssistantActionTool
  {
    return collect($this->tools)->first(
      fn(AssistantActionTool $tool) => $tool->name() === $name
    );
  }
}
