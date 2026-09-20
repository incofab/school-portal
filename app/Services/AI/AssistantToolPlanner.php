<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Support\AI\AssistantRedactor;
use App\Support\Audit\ActivityLogger;
use Illuminate\Support\Facades\Log;
use App\Models\AiConversation;
use Illuminate\Support\Str;

class AssistantToolPlanner
{
  public function __construct(
    private readonly AssistantToolRegistry $registry,
    private readonly ActivityLogger $activityLogger,
    private readonly AssistantRedactor $redactor
  ) {
  }

  /**
   * Select only deterministic, read-only capabilities from explicit request language.
   * The model never supplies tool names or authorization decisions in this phase.
   */
  public function run(
    AiConversation $conversation,
    string $message,
    AssistantActorContext $context,
    AssistantRunBudget $budget
  ): array {
    $lower = Str::lower($message);
    $entities = $conversation->entities ?? [];
    $plans = [];

    if (
      Str::contains($lower, [
        'school',
        'institution',
        'profile',
        'contact details'
      ])
    ) {
      $plans[] = ['name' => 'view_institution_profile', 'arguments' => []];
    }

    if (
      Str::contains($lower, [
        'academic session',
        'academic year',
        'term',
        'session'
      ])
    ) {
      $plans[] = ['name' => 'list_academic_sessions', 'arguments' => []];
    }

    if (Str::contains($lower, ['staff', 'teacher', 'teachers', 'employees'])) {
      $plans[] = ['name' => 'list_authorized_staff', 'arguments' => []];
    }

    if (Str::contains($lower, ['class', 'classes', 'jss', 'sss', 'primary'])) {
      $plans[] = ['name' => 'list_authorized_classes', 'arguments' => []];
    }

    if (
      Str::contains($lower, [
        'student',
        'students',
        'learner',
        'learners',
        'pupil'
      ])
    ) {
      $plans[] = [
        'name' => 'list_authorized_students',
        'arguments' => []
      ];
    }

    if (
      Str::contains($lower, [
        'result',
        'results',
        'score',
        'scores',
        'grade',
        'grades'
      ]) &&
      isset($entities['academic_session'], $entities['term'])
    ) {
      $plans[] = [
        'name' => 'view_published_student_result',
        'arguments' => [
          'student' =>
            $entities['student'] ??
            ($this->isSelfRequest($lower) ? 'me' : null),
          'academic_session' => $entities['academic_session'],
          'term' => Str::before($entities['term'], ' Term')
        ]
      ];
    }

    return collect($plans)
      ->unique('name')
      ->take(config('ai.assistant.max_tool_calls', 5))
      ->map(function (array $plan) use ($conversation, $context, $budget) {
        return $this->executeReadOnlyTool(
          $conversation,
          $plan['name'],
          $plan['arguments'],
          $context,
          $budget
        );
      })
      ->values()
      ->all();
  }

  /**
   * Convert registered read-only capabilities into Laravel AI tools. The
   * callback records the same safe observation shape used by the deterministic
   * planner so the UI and telemetry do not depend on the provider response.
   *
   * @param  array<int, array<string,mixed>>  $observations
   * @return array<int, RegisteredAssistantTool>
   */
  public function laravelAiTools(
    AiConversation $conversation,
    AssistantActorContext $context,
    AssistantRunBudget $budget,
    array &$observations = []
  ): array {
    if (
      $context->isGuest ||
      !$context->institutionUser ||
      !$context->institutionUser->isActive()
    ) {
      return [];
    }

    return collect($this->registry->definitions())
      ->map(function (array $definition) use (
        $conversation,
        $context,
        $budget,
        &$observations
      ): RegisteredAssistantTool {
        return new RegisteredAssistantTool(
          $definition['name'],
          $definition['description'],
          $definition['input_schema'] ?? [],
          function (array $arguments) use (
            $conversation,
            $context,
            $budget,
            $definition,
            &$observations
          ): string {
            $observation = $this->executeReadOnlyTool(
              $conversation,
              $definition['name'],
              $arguments,
              $context,
              $budget
            );
            $observations[] = $observation;

            try {
              return json_encode(
                $observation['result'],
                JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
              );
            } catch (\Throwable $exception) {
              Log::warning(
                'EduManager AI assistant tool result encoding failed.',
                [
                  'conversation_id' => $conversation->getKey(),
                  'tool' => $definition['name'],
                  'exception' => $exception::class
                ]
              );

              return json_encode([
                'ok' => false,
                'message' =>
                  'The read-only capability returned an unusable result.',
                'data' => [],
                'meta' => ['reason' => 'invalid_result']
              ]);
            }
          }
        );
      })
      ->values()
      ->all();
  }

  /**
   * Execute a registry tool once and return the redacted, persistence-safe
   * observation consumed by both deterministic and native model loops.
   */
  private function executeReadOnlyTool(
    AiConversation $conversation,
    string $toolName,
    array $arguments,
    AssistantActorContext $context,
    AssistantRunBudget $budget
  ): array {
    try {
      $budget->consumeToolCall();
      $result = $this->registry->execute($toolName, $arguments, $context);
    } catch (\Throwable $exception) {
      Log::warning('EduManager AI assistant read-only tool failed.', [
        'conversation_id' => $conversation->getKey(),
        'tool' => $toolName,
        'exception' => $exception::class
      ]);
      $result = AssistantToolResult::invalid(
        'The read-only capability could not be completed.'
      );
    }

    $this->auditToolUse(
      $conversation,
      $toolName,
      $arguments,
      $result,
      $context
    );

    return [
      'name' => $toolName,
      'arguments' => $arguments,
      'result' => $result->toArray()
    ];
  }

  private function auditToolUse(
    AiConversation $conversation,
    string $toolName,
    array $arguments,
    AssistantToolResult $result,
    AssistantActorContext $context
  ): void {
    try {
      $this->activityLogger
        ->event('assistant.tool.accessed')
        ->category('authorization')
        ->action('tool_accessed')
        ->by($context->user)
        ->inInstitution($context->institution)
        ->description('Assistant read-only tool access.')
        ->properties(
          $this->redactor->properties([
            'conversation_id' => $conversation->getKey(),
            'user_id' => $context->userId(),
            'tool' => $toolName,
            'argument_keys' => array_keys($arguments),
            'ok' => $result->ok,
            'reason' => $result->meta['reason'] ?? null
          ])
        )
        ->log();
    } catch (\Throwable $exception) {
      Log::warning('EduManager AI assistant tool audit failed.', [
        'conversation_id' => $conversation->getKey(),
        'tool' => $toolName,
        'exception' => $exception::class
      ]);
    }
  }

  private function isSelfRequest(string $message): bool
  {
    return Str::contains($message, [
      'my result',
      'my score',
      'my grade',
      'for me'
    ]);
  }
}
