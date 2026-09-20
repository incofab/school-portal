<?php

namespace App\Services\AI\Tools;

use App\Actions\RecordStaff;
use App\DTO\AI\AssistantActionPreview;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;

class UpdateStaffActionTool extends AbstractStaffActionTool
{
  public function name(): string
  {
    return 'update_staff';
  }

  public function description(): string
  {
    return 'Prepare and, after confirmation, update a staff member in the current institution.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [
        ...$this->staffProperties(),
        'institution_user_id' => ['type' => 'integer']
      ],
      'required' => ['email']
    ];
  }

  public function preview(
    array $arguments,
    AssistantActorContext $context
  ): AssistantActionPreview|AssistantToolResult {
    if (!$this->isAuthorized($context)) {
      return AssistantToolResult::denied(
        'Only an active institution administrator can update a staff member.'
      );
    }

    if ($mismatch = $this->ambientInstitutionMismatch($context)) {
      return $mismatch;
    }

    $staff = $this->findStaff($arguments, $context);
    if (!$staff?->user) {
      return AssistantToolResult::invalid(
        'Tell me which staff member to update, by their email address or staff id.'
      );
    }

    // Only the supplied fields change; everything else keeps its stored value
    // so the shared staff rules validate a complete record.
    $arguments = [
      ...$staff->user->only([
        'first_name',
        'last_name',
        'other_names',
        'email',
        'phone',
        'gender'
      ]),
      ...collect($arguments)
        ->only([
          'first_name',
          'last_name',
          'other_names',
          'email',
          'phone',
          'gender',
          'role'
        ])
        ->filter(fn($value) => filled($value))
        ->all()
    ];
    $arguments = $this->withResolvedRole($arguments, $context);

    $validation = $this->validationFailure(
      $arguments,
      $this->staffRules($context, $staff->user_id, false)
    );

    if ($validation) {
      return $validation;
    }

    return new AssistantActionPreview(
      tool: $this->name(),
      title: 'Update staff member',
      summary: "Update the staff record for {$staff->user->full_name}.",
      arguments: [...$arguments, 'institution_user_id' => $staff->getKey()],
      changes: [
        'staff' => $staff->user->full_name,
        'email' => $arguments['email'],
        'name' => $this->staffName($arguments),
        'phone' => $arguments['phone'] ?? null,
        'role' => isset($arguments['role'])
          ? $this->roleName($context, $arguments['role'])
          : null
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

    $staff = $this->findStaff($preview->arguments, $context);
    if (!$staff?->user) {
      return AssistantToolResult::invalid(
        'That staff member is no longer available in this institution.'
      );
    }

    $user = RecordStaff::make(
      $context->institution,
      collect($preview->arguments)
        ->except('institution_user_id')
        ->all()
    )->update($staff->user);

    return AssistantToolResult::success(
      "{$user->full_name} was updated successfully.",
      ['staff' => $user->only(['id', 'first_name', 'last_name', 'email'])],
      ['idempotency_key' => $idempotencyKey]
    );
  }
}
