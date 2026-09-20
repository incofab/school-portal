<?php

namespace App\Services\AI;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class LaravelAiTextAgent implements Agent, HasTools
{
  use Promptable;

  /**
   * @param  array<int, object>  $tools
   */
  public function __construct(
    private readonly string $systemPrompt,
    private readonly array $tools = [],
    private readonly ?int $stepLimit = null,
    private readonly ?int $tokenLimit = null
  ) {
  }

  public function instructions(): string
  {
    return $this->systemPrompt;
  }

  public function tools(): iterable
  {
    return $this->tools;
  }

  public function maxSteps(): ?int
  {
    return $this->stepLimit;
  }

  public function maxTokens(): ?int
  {
    return $this->tokenLimit;
  }
}
