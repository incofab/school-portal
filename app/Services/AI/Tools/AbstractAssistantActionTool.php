<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantActionTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use Illuminate\Support\Facades\Validator;

abstract class AbstractAssistantActionTool implements AssistantActionTool
{
  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    return AssistantToolResult::invalid(
      'This operation must be reviewed and explicitly confirmed before it can run.'
    );
  }

  protected function adminCanAct(AssistantActorContext $context): bool
  {
    return !$context->isGuest &&
      $context->institution !== null &&
      $context->institutionUser?->isActive() &&
      $context->institutionUser?->isAdmin();
  }

  /**
   * The shared model rules (Classification::createRule(), Course::createRule(),
   * User::generalRule()) scope themselves through the InstitutionScope global
   * scope, which reads the institution bound to the current route. Refuse to
   * run when that ambient institution is not the actor's institution so a
   * rule can never be evaluated against the wrong tenant.
   */
  protected function ambientInstitutionMismatch(
    AssistantActorContext $context
  ): ?AssistantToolResult {
    if (currentInstitution()?->getKey() === $context->institution?->getKey()) {
      return null;
    }

    return AssistantToolResult::denied(
      'This action can only be prepared from within the institution it belongs to.'
    );
  }

  protected function validationFailure(
    array $arguments,
    array $rules
  ): ?AssistantToolResult {
    $validator = Validator::make($arguments, $rules);

    if ($validator->fails()) {
      return AssistantToolResult::invalid($validator->errors()->first());
    }

    return null;
  }
}
