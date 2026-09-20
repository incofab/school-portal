<?php

namespace App\DTO\AI;

readonly final class AssistantActionPreview
{
  public function __construct(
    public string $tool,
    public string $title,
    public string $summary,
    public array $arguments,
    public array $changes = []
  ) {
  }

  public function toArray(): array
  {
    return [
      'tool' => $this->tool,
      'title' => $this->title,
      'summary' => $this->summary,
      'arguments' => $this->arguments,
      'changes' => $this->changes
    ];
  }
}
