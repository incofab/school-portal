<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Services\AI\AssistantToolScope;

class ListAcademicSessionsTool implements AssistantTool
{
    public function __construct(private readonly AssistantToolScope $scope) {}

    public function name(): string
    {
        return 'list_academic_sessions';
    }

    public function description(): string
    {
        return 'List available academic sessions and supported terms.';
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

        return AssistantToolResult::success('Academic sessions retrieved.', [
            'sessions' => AcademicSession::query()
                ->orderByDesc('is_active')
                ->orderByDesc('order_index')
                ->orderByDesc('id')
                ->get(['id', 'title', 'is_active'])
                ->map(fn (AcademicSession $session) => [
                    'id' => $session->id,
                    'title' => $session->title,
                    'is_active' => $session->is_active,
                ])
                ->all(),
            'terms' => collect(TermType::cases())->map(fn (TermType $term) => [
                'value' => $term->value,
                'label' => ucfirst($term->value).' Term',
            ])->all(),
        ]);
    }
}
