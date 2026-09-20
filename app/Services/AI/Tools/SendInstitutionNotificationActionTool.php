<?php

namespace App\Services\AI\Tools;

use App\Actions\Notifications\CreateInternalNotification;
use App\DTO\AI\AssistantActionPreview;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Enums\InstitutionPermission;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class SendInstitutionNotificationActionTool extends AbstractAssistantActionTool
{
  public function name(): string
  {
    return 'send_institution_notification';
  }

  public function description(): string
  {
    return 'Prepare and, after confirmation, send an internal notification to an explicitly selected class or class group.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'title' => ['type' => 'string'],
        'body' => ['type' => 'string'],
        'target_type' => [
          'type' => 'string',
          'enum' => ['classification', 'classification-group']
        ],
        'target_id' => ['type' => 'integer']
      ],
      'required' => ['title', 'target_type', 'target_id']
    ];
  }

  public function isAuthorized(AssistantActorContext $context): bool
  {
    return !$context->isGuest &&
      $context->institution !== null &&
      $context->institutionUser?->isActive() &&
      $context->institutionUser?->hasInstitutionPermission(
        InstitutionPermission::ManageNotifications
      );
  }

  public function preview(
    array $arguments,
    AssistantActorContext $context
  ): AssistantActionPreview|AssistantToolResult {
    if (!$this->isAuthorized($context)) {
      return AssistantToolResult::denied(
        'This account is not allowed to send institution notifications.'
      );
    }

    if ($mismatch = $this->ambientInstitutionMismatch($context)) {
      return $mismatch;
    }

    $validation = $this->validationFailure($arguments, [
      'title' => ['required', 'string', 'max:255'],
      'body' => ['nullable', 'string'],
      'target_type' => [
        'required',
        Rule::in(['classification', 'classification-group'])
      ],
      'target_id' => ['required', 'integer']
    ]);

    if ($validation) {
      return $validation;
    }

    $target = $this->target($arguments, $context);
    if (!$target) {
      return AssistantToolResult::invalid(
        'The selected notification target was not found in this institution.'
      );
    }

    return new AssistantActionPreview(
      tool: $this->name(),
      title: 'Send internal notification',
      summary: "Send “{$arguments['title']}” to {$target->title}.",
      arguments: $arguments,
      changes: [
        'title' => $arguments['title'],
        'target' => $target->title,
        'target_type' => $arguments['target_type'],
        'body' => $arguments['body'] ?? null
      ]
    );
  }

  public function executeConfirmed(
    array $arguments,
    AssistantActorContext $context,
    string $idempotencyKey
  ): AssistantToolResult {
    $preview = $this->preview($arguments, $context);
    if ($preview instanceof AssistantToolResult) {
      return $preview;
    }

    $notification = app(CreateInternalNotification::class)->execute(
      sender: $context->institutionUser,
      targets: [$this->target($preview->arguments, $context)],
      title: $preview->arguments['title'],
      body: $preview->arguments['body'] ?? null,
      institution: $context->institution
    );

    return AssistantToolResult::success(
      'The notification was sent successfully.',
      [
        'notification' => $notification->only(['id', 'title', 'institution_id'])
      ],
      ['idempotency_key' => $idempotencyKey]
    );
  }

  private function target(
    array $arguments,
    AssistantActorContext $context
  ): ?Model {
    $model = match ($arguments['target_type'] ?? null) {
      'classification' => Classification::class,
      'classification-group' => ClassificationGroup::class,
      default => null
    };

    if (!$model) {
      return null;
    }

    return $model
      ::query()
      ->where('institution_id', $context->institution->id)
      ->find($arguments['target_id'] ?? null);
  }
}
