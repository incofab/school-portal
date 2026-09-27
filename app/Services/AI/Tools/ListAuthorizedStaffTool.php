<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\InstitutionUser;
use App\Services\AI\AssistantToolScope;

class ListAuthorizedStaffTool implements AssistantTool
{
  public function __construct(private readonly AssistantToolScope $scope)
  {
  }

  public function name(): string
  {
    return 'list_authorized_staff';
  }

  public function description(): string
  {
    return 'List active staff and teachers in the current institution without exposing private contact details.';
  }

  public function inputSchema(): array
  {
    return [
      'type' => 'object',
      'properties' => [],
      'additionalProperties' => false
    ];
  }

  public function execute(
    array $arguments,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->scope->canAccessInstitution($context)) {
      return AssistantToolResult::denied();
    }

    $staffQuery = InstitutionUser::query()
      ->where('institution_id', $context->institution->id)
      ->whereIn('type', ['admin', 'teacher', 'accountant'])
      ->where('status', 'active')
      ->with('user');
    $total = (clone $staffQuery)->count();
    $staff = $staffQuery
      ->orderBy('id')
      ->limit(200)
      ->get();

    return AssistantToolResult::success(
      'Authorized staff retrieved.',
      [
        'staff_count' => $total,
        'staff' => $staff
          ->map(
            fn(InstitutionUser $institutionUser) => [
              'id' => $institutionUser->id,
              'user_id' => $institutionUser->user_id,
              'name' => $institutionUser->user?->full_name,
              'role' => $institutionUser->type?->value
            ]
          )
          ->all()
      ],
      ['limited' => $total > 200, 'limit' => 200]
    );
  }
}
