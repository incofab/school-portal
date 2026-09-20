<?php

namespace App\Services\AI\Tools;

use App\DTO\AI\AssistantActionPreview;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Course;

class CreateSubjectActionTool extends AbstractAssistantActionTool
{
  public function name(): string
  {
    return 'create_subject';
  }

  public function description(): string
  {
    return 'Prepare and, after confirmation, create a subject in the current institution.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        'title' => ['type' => 'string'],
        'code' => ['type' => 'string'],
        'description' => ['type' => 'string'],
        'category' => ['type' => 'string']
      ],
      'required' => ['title', 'code']
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
        'Only an active institution administrator can create a subject.'
      );
    }

    if ($mismatch = $this->ambientInstitutionMismatch($context)) {
      return $mismatch;
    }

    $validation = $this->validationFailure($arguments, Course::createRule());

    if ($validation) {
      return $validation;
    }

    return new AssistantActionPreview(
      tool: $this->name(),
      title: 'Create subject',
      summary: "Create {$arguments['title']} ({$arguments['code']}).",
      arguments: $arguments,
      changes: [
        'subject_title' => $arguments['title'],
        'subject_code' => $arguments['code']
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

    $course = $context->institution->courses()->create($preview->arguments);

    return AssistantToolResult::success(
      "Subject {$course->title} was created successfully.",
      ['subject' => $course->only(['id', 'title', 'code'])],
      ['idempotency_key' => $idempotencyKey]
    );
  }
}
