<?php

namespace App\Services\AI\Tools;

use App\Actions\RecordStaff;
use App\DTO\AI\AssistantActionPreview;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Support\Audit\ModelAudit;
use App\Support\Audit\SecurityActivityLogger;

class CreateStaffActionTool extends AbstractStaffActionTool
{
  public function name(): string
  {
    return 'create_staff';
  }

  public function description(): string
  {
    return 'Prepare and, after confirmation, add a staff member to the current institution.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => $this->staffProperties(),
      'required' => ['first_name', 'last_name', 'email', 'role']
    ];
  }

  public function preview(
    array $arguments,
    AssistantActorContext $context
  ): AssistantActionPreview|AssistantToolResult {
    if (!$this->isAuthorized($context)) {
      return AssistantToolResult::denied(
        'Only an active institution administrator can add a staff member.'
      );
    }

    if ($mismatch = $this->ambientInstitutionMismatch($context)) {
      return $mismatch;
    }

    $missing = collect(['first_name', 'last_name', 'email', 'role'])
      ->reject(fn(string $field) => filled($arguments[$field] ?? null))
      ->values()
      ->all();

    if ($missing) {
      return $this->missingDetails($missing);
    }

    $arguments = $this->withResolvedRole($arguments, $context);

    $validation = $this->validationFailure(
      $arguments,
      $this->staffRules($context, null, true)
    );

    if ($validation) {
      return $validation;
    }

    $roleName = $this->roleName($context, $arguments['role']);
    $name = $this->staffName($arguments);

    return new AssistantActionPreview(
      tool: $this->name(),
      title: 'Add staff member',
      summary: "Add {$name} as {$roleName} in {$context->institution->name}.",
      arguments: $arguments,
      changes: [
        'name' => $name,
        'email' => $arguments['email'],
        'role' => $roleName,
        'phone' => $arguments['phone'] ?? null,
        'starting_password' =>
          'The account is created with the standard starting password and must be reset on first sign-in.'
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

    $user = ModelAudit::withoutAuditingFor(
      [User::class, InstitutionUser::class],
      fn() => RecordStaff::make(
        $context->institution,
        $preview->arguments
      )->create()
    );

    app(SecurityActivityLogger::class)->userCreated(
      $context->user,
      $user,
      $context->institution,
      $this->roleName($context, $preview->arguments['role']) ?? ''
    );

    return AssistantToolResult::success(
      "{$user->full_name} was added to the staff list successfully.",
      ['staff' => $user->only(['id', 'first_name', 'last_name', 'email'])],
      ['idempotency_key' => $idempotencyKey]
    );
  }
}
