<?php

namespace App\Services\AI\Tools;

use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Enums\InstitutionUserType;
use App\Models\InstitutionUser;
use App\Models\User;
use App\Services\Institutions\InstitutionRoleService;
use Illuminate\Support\Str;

/**
 * Shared behaviour for the assistant's staff actions. Both the create and the
 * update action run through the same rules that CreateStaffRequest uses and
 * execute through the existing RecordStaff action.
 */
abstract class AbstractStaffActionTool extends AbstractAssistantActionTool
{
  public function __construct(protected readonly InstitutionRoleService $roles)
  {
  }

  public function isAuthorized(AssistantActorContext $context): bool
  {
    return $this->adminCanAct($context);
  }

  protected function staffProperties(): array
  {
    return [
      'first_name' => ['type' => 'string'],
      'last_name' => ['type' => 'string'],
      'other_names' => ['type' => 'string'],
      'email' => ['type' => 'string'],
      'phone' => ['type' => 'string'],
      'gender' => ['type' => 'string'],
      'role' => [
        'type' => 'string',
        'description' => 'A staff role id or staff role name.'
      ]
    ];
  }

  /**
   * The staff rules used by CreateStaffRequest, minus the password fields.
   * RecordStaff always assigns the institution's default starting password,
   * so the assistant never accepts a password through conversation.
   */
  protected function staffRules(
    AssistantActorContext $context,
    ?int $userId,
    bool $roleRequired
  ): array {
    return [
      ...collect(User::generalRule($userId))
        ->except(['password', 'password_confirmation'])
        ->all(),
      'role' => [
        $roleRequired ? 'required' : 'nullable',
        'integer',
        $this->roles->staffRoleRule($context->institution)
      ]
    ];
  }

  /**
   * Accept a role given as an id or as a role name and normalise it to an id
   * so the shared role rule can validate it.
   */
  protected function withResolvedRole(
    array $arguments,
    AssistantActorContext $context
  ): array {
    if (blank($arguments['role'] ?? null)) {
      unset($arguments['role']);

      return $arguments;
    }

    $arguments['role'] = $this->roles->resolveStaffRoleId(
      $context->institution,
      $arguments['role']
    );

    return $arguments;
  }

  /**
   * Resolve the staff member an update refers to, by institution user id or by
   * email, never outside the actor's institution and never a non-staff member.
   */
  protected function findStaff(
    array $arguments,
    AssistantActorContext $context
  ): ?InstitutionUser {
    $query = InstitutionUser::query()
      ->where('institution_id', $context->institution->id)
      ->whereNotIn('type', InstitutionUserType::nonStaffRoles())
      ->with('user');

    if (filled($arguments['institution_user_id'] ?? null)) {
      return $query->find($arguments['institution_user_id']);
    }

    if (filled($arguments['email'] ?? null)) {
      return $query
        ->whereHas(
          'user',
          fn($user) => $user->where('email', Str::lower($arguments['email']))
        )
        ->first();
    }

    return null;
  }

  protected function staffName(array $arguments): string
  {
    return collect([
      $arguments['first_name'] ?? null,
      $arguments['other_names'] ?? null,
      $arguments['last_name'] ?? null
    ])
      ->filter()
      ->implode(' ');
  }

  protected function roleName(AssistantActorContext $context, $roleId): ?string
  {
    return $this->roles
      ->forStaff($context->institution)
      ->firstWhere('id', (int) $roleId)?->name;
  }

  protected function missingDetails(array $missing): AssistantToolResult
  {
    return AssistantToolResult::invalid(
      'I still need ' .
        collect($missing)
          ->map(fn(string $field) => str_replace('_', ' ', $field))
          ->join(', ', ' and ') .
        ' before I can prepare this staff record.'
    );
  }
}
