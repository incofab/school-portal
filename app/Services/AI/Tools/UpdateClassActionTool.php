<?php

namespace App\Services\AI\Tools;

use App\DTO\AI\AssistantActionPreview;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Classification;

class UpdateClassActionTool extends AbstractAssistantActionTool
{
  public function name(): string
  {
    return 'update_class';
  }

  public function description(): string
  {
    return 'Prepare and, after confirmation, update a class in the current institution.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'classification_id' => ['type' => 'integer'],
        'title' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'has_equal_subjects' => ['type' => 'boolean']
      ],
      'required' => ['classification_id', 'title']
    ];
  }

  public function isAuthorized(AssistantActorContext $context): bool
  {
    return $this->adminCanAct($context);
  }

  public function preview(
    array $arguments,
    AssistantActorContext $context
  ): AssistantActionPreview|AssistantToolResult {
    if (!$this->isAuthorized($context)) {
      return AssistantToolResult::denied(
        'Only an active institution administrator can update a class.'
      );
    }

    if ($mismatch = $this->ambientInstitutionMismatch($context)) {
      return $mismatch;
    }

    $classification = Classification::query()
      ->where('institution_id', $context->institution->id)
      ->find($arguments['classification_id'] ?? null);

    if (!$classification) {
      return AssistantToolResult::invalid(
        'The selected class was not found in this institution.'
      );
    }

    // Default the unchanged fields from the stored class so the shared
    // Classification rules validate a complete record.
    $arguments['classification_group_id'] ??=
      $classification->classification_group_id;

    $validation = $this->validationFailure($arguments, [
      ...Classification::createRule($classification),
      'classification_id' => ['required', 'integer']
    ]);

    if ($validation) {
      return $validation;
    }

    return new AssistantActionPreview(
      tool: $this->name(),
      title: 'Update class',
      summary: "Rename {$classification->title} to {$arguments['title']}.",
      arguments: $arguments,
      changes: [
        'class_id' => $classification->id,
        'current_title' => $classification->title,
        'new_title' => $arguments['title']
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

    $classification = Classification::query()
      ->where('institution_id', $context->institution->id)
      ->findOrFail($preview->arguments['classification_id']);
    $classification
      ->fill(
        collect($preview->arguments)
          ->except('classification_id')
          ->all()
      )
      ->save();

    return AssistantToolResult::success(
      "Class {$classification->title} was updated successfully.",
      ['class' => $classification->only(['id', 'title', 'description'])],
      ['idempotency_key' => $idempotencyKey]
    );
  }
}
