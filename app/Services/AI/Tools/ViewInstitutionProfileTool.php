<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Services\AI\AssistantToolScope;

class ViewInstitutionProfileTool implements AssistantTool
{
    public function __construct(private readonly AssistantToolScope $scope) {}

    public function name(): string
    {
        return 'view_institution_profile';
    }

    public function description(): string
    {
        return 'View the current institution profile and public contact details.';
    }

    public function inputSchema(): array
    {
        return ['type' => 'object', 'properties' => [], 'additionalProperties' => false];
    }

    public function execute(array $arguments, AssistantActorContext $context): AssistantToolResult
    {
        if (! $this->scope->canAccessInstitution($context)) {
            return AssistantToolResult::denied();
        }

        $institution = $context->institution;

        return AssistantToolResult::success('Institution profile retrieved.', [
            'id' => $institution->getKey(),
            'name' => $institution->name,
            'code' => $institution->code,
            'status' => $institution->status?->value ?? $institution->status,
            'address' => $institution->address,
            'email' => $institution->email,
            'phone' => $institution->phone,
            'website' => $institution->website,
        ]);
    }
}
