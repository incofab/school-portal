<?php

namespace App\Services\AI\Tools;

use App\Contracts\AI\AssistantTool;
use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\Student;
use App\Services\AI\AssistantToolScope;

class ListAuthorizedStudentsTool implements AssistantTool
{
    public function __construct(private readonly AssistantToolScope $scope) {}

    public function name(): string
    {
        return 'list_authorized_students';
    }

    public function description(): string
    {
        return 'List students within the current actor and institution access scope.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classification_id' => ['type' => 'integer'],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments, AssistantActorContext $context): AssistantToolResult
    {
        if (! $this->scope->canAccessInstitution($context)) {
            return AssistantToolResult::denied();
        }

        $students = $this->scope->students($context);
        if (! $students) {
            return AssistantToolResult::denied();
        }

        if (is_scalar($arguments['classification_id'] ?? null)) {
            $students->where('classification_id', (int) $arguments['classification_id']);
        }

        return AssistantToolResult::success('Authorized students retrieved.', [
            'students' => $students
                ->orderBy('id')
                ->limit(200)
                ->get()
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'code' => $student->code,
                    'name' => $student->user?->full_name,
                    'class' => $student->classification?->title,
                ])
                ->all(),
        ], ['limited' => true, 'limit' => 200]);
    }
}
