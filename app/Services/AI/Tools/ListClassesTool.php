<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Classification;
use App\Services\AI\AssistantToolScope;

class ListClassesTool implements AssistantTool
{
    public function __construct(private readonly AssistantToolScope $scope) {}

    public function name(): string
    {
        return 'list_authorized_classes';
    }

    public function description(): string
    {
        return 'List classes the current actor is authorized to see.';
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

        $studentScope = $this->scope->students($context);
        if (! $studentScope) {
            return AssistantToolResult::denied();
        }

        $classIds = $studentScope->clone()->select('classification_id');
        $classes = Classification::query()
            ->where('institution_id', $context->institution->id)
            ->when(
                ! $context->institutionUser->isAdmin() && (
                    $context->institutionUser->isTeacher()
                    || ! $context->institutionUser->isStaff()
                ),
                fn ($query) => $query->whereIn('id', $classIds)
            )
            ->withCount('students')
            ->orderBy('title')
            ->get(['id', 'title', 'description']);

        return AssistantToolResult::success('Authorized classes retrieved.', [
            'classes' => $classes->map(fn (Classification $classification) => [
                'id' => $classification->id,
                'title' => $classification->title,
                'description' => $classification->description,
                'student_count' => $classification->students_count,
            ])->all(),
        ]);
    }
}
