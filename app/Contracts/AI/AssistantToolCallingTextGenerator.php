<?php

namespace App\Contracts\AI;

use App\DTO\AI\AssistantGenerationResult;

interface AssistantToolCallingTextGenerator
{
  /**
   * @param  array<int, object>  $tools
   */
  public function generateWithTools(
    string $systemPrompt,
    string $prompt,
    array $tools,
    int $maxSteps
  ): AssistantGenerationResult;
}
