<?php

namespace App\Contracts\AI;

use App\DTO\AI\AssistantGenerationResult;

interface AssistantTextGenerator
{
  public function generate(
    string $systemPrompt,
    string $prompt,
    array $options = []
  ): AssistantGenerationResult;
}
