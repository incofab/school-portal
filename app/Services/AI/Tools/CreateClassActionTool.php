<?php

namespace App\Services\AI\Tools;

use App\DTO\AI\AssistantActionPreview;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Classification;
use App\Models\ClassificationGroup;

class CreateClassActionTool extends AbstractAssistantActionTool
{
  public function name(): string
  {
    return 'create_class';
  }

  public function description(): string
  {
    return 'Prepare and, after confirmation, create a class in the current institution.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'title' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'classification_group_id' => ['type' => 'integer'],
        'form_teacher_id' => ['type' => 'integer'],
        'has_equal_subjects' => ['type' => 'boolean']
      ],
      'required' => ['title', 'classification_group_id']
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
        'Only an active institution administrator can create a class.'
      );
    }

    if ($mismatch = $this->ambientInstitutionMismatch($context)) {
      return $mismatch;
    }

    $arguments = $this->withDefaultClassificationGroup($arguments, $context);
    if ($arguments instanceof AssistantToolResult) {
      return $arguments;
    }

    $validation = $this->validationFailure(
      $arguments,
      Classification::createRule()
    );

    if ($validation) {
      if (
        str_starts_with(
          (string) $validation->message,
          'classification_group_id does not exist'
        )
      ) {
        return AssistantToolResult::invalid(
          'The selected classification group was not found in this institution.'
        );
      }

      return $validation;
    }

    // Classification::createRule() is intentionally shared with the web
    // workflow and uses the ambient institution scope. Keep this explicit
    // lookup as a second boundary so a malformed or cross-tenant id can never
    // become a null dereference or appear in the preview.
    $group = ClassificationGroup::query()
      ->where('institution_id', $context->institution->id)
      ->find($arguments['classification_group_id']);

    if (!$group) {
      return AssistantToolResult::invalid(
        'The selected classification group was not found in this institution.'
      );
    }

    return new AssistantActionPreview(
      tool: $this->name(),
      title: 'Create class',
      summary: "Create {$arguments['title']} under {$group->title}.",
      arguments: $arguments,
      changes: [
        'class_title' => $arguments['title'],
        'classification_group' => $group->title,
        'form_teacher_id' => $arguments['form_teacher_id'] ?? null
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

    $classification = $context->institution
      ->classifications()
      ->create([
        ...$preview->arguments,
        'has_equal_subjects' =>
          $preview->arguments['has_equal_subjects'] ?? true
      ]);

    return AssistantToolResult::success(
      "Class {$classification->title} was created successfully.",
      [
        'class' => $classification->only([
          'id',
          'title',
          'classification_group_id'
        ])
      ],
      ['idempotency_key' => $idempotencyKey]
    );
  }

  private function withDefaultClassificationGroup(
    array $arguments,
    AssistantActorContext $context
  ): array|AssistantToolResult {
    if (isset($arguments['classification_group_id'])) {
      return $arguments;
    }

    $groups = $context->institution->classificationGroups()->get();
    if ($groups->count() !== 1) {
      return AssistantToolResult::invalid(
        'Tell me which classification group to use, for example “group 2”.'
      );
    }

    $arguments['classification_group_id'] = $groups->first()->id;

    return $arguments;
  }
}
