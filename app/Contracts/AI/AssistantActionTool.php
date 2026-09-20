<?php

namespace App\Contracts\AI;

use App\DTO\AI\AssistantActionPreview;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;

interface AssistantActionTool extends AssistantTool
{
  public function isAuthorized(AssistantActorContext $context): bool;

  public function preview(
    array $arguments,
    AssistantActorContext $context
  ): AssistantActionPreview|AssistantToolResult;

  public function executeConfirmed(
    array $arguments,
    AssistantActorContext $context,
    string $idempotencyKey
  ): AssistantToolResult;
}
