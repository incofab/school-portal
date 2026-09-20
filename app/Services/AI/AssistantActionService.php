<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\DTO\AI\AssistantToolResult;
use App\Models\AiConversation;
use App\Models\AssistantActionExecution;
use App\Support\AI\AssistantRedactor;
use App\Support\Audit\ActivityLogger;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AssistantActionService
{
  public function __construct(
    private readonly AssistantActionRegistry $registry,
    private readonly AssistantConversationService $conversations,
    private readonly DatabaseManager $database,
    private readonly ActivityLogger $activityLogger,
    private readonly AssistantRedactor $redactor
  ) {
  }

  public function prepare(
    AiConversation $conversation,
    string $toolName,
    array $arguments,
    AssistantActorContext $context
  ): AssistantActionExecution|AssistantToolResult {
    if (!$this->contextOwnsConversation($conversation, $context)) {
      return AssistantToolResult::denied(
        'This action does not belong to the current assistant conversation.'
      );
    }

    $tool = $this->registry->get($toolName);
    if (!$tool) {
      return AssistantToolResult::invalid(
        'That assistant action is not available.'
      );
    }

    if (!$tool->isAuthorized($context)) {
      $result = AssistantToolResult::denied(
        'This account is not allowed to perform that action.'
      );
      $this->log(
        'assistant.action.denied',
        'action_denied',
        $conversation,
        $context,
        ['tool' => $toolName, 'reason' => 'authorization']
      );

      return $result;
    }

    $preview = $tool->preview($arguments, $context);
    if ($preview instanceof AssistantToolResult) {
      return $preview;
    }

    $confirmationToken = Str::random(64);
    $execution = AssistantActionExecution::query()->create([
      'id' => Str::orderedUuid()->toString(),
      'conversation_id' => $conversation->getKey(),
      'institution_id' => $context->institution?->getKey(),
      'tool_name' => $toolName,
      'idempotency_key' => 'assistant-action:' . Str::orderedUuid()->toString(),
      'confirmation_token_hash' => hash('sha256', $confirmationToken),
      'arguments' => $preview->arguments,
      'preview' => $preview->toArray(),
      'status' => 'pending'
    ]);
    $execution->confirmation_token = $confirmationToken;

    $this->log(
      'assistant.action.previewed',
      'action_previewed',
      $conversation,
      $context,
      [
        'tool' => $toolName,
        'execution_id' => $execution->getKey(),
        'idempotency_key' => $execution->idempotency_key,
        'preview' => $preview->toArray()
      ]
    );

    return $execution;
  }

  public function confirm(
    AiConversation $conversation,
    string $executionId,
    string $confirmationToken,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->contextOwnsConversation($conversation, $context)) {
      return AssistantToolResult::denied(
        'This action does not belong to the current assistant conversation.'
      );
    }

    try {
      $result = $this->database->transaction(function () use (
        $conversation,
        $executionId,
        $confirmationToken,
        $context
      ) {
        $execution = AssistantActionExecution::query()
          ->where('conversation_id', $conversation->getKey())
          ->whereKey($executionId)
          ->lockForUpdate()
          ->first();

        if (!$execution) {
          return AssistantToolResult::invalid(
            'The requested assistant action could not be found.'
          );
        }

        if (
          (int) $execution->institution_id !== (int) $context->institutionId()
        ) {
          return AssistantToolResult::denied(
            'This assistant action belongs to another institution.'
          );
        }

        if (
          !hash_equals(
            $execution->confirmation_token_hash,
            hash('sha256', $confirmationToken)
          )
        ) {
          $this->log(
            'assistant.action.denied',
            'action_denied',
            $conversation,
            $context,
            [
              'tool' => $execution->tool_name,
              'reason' => 'invalid_confirmation'
            ]
          );

          return AssistantToolResult::denied(
            'This confirmation is no longer valid.'
          );
        }

        $tool = $this->registry->get($execution->tool_name);
        if (!$tool || !$tool->isAuthorized($context)) {
          $this->log(
            'assistant.action.denied',
            'action_denied',
            $conversation,
            $context,
            ['tool' => $execution->tool_name, 'reason' => 'authorization']
          );

          if ($execution->isPending()) {
            $execution
              ->forceFill([
                'status' => 'failed',
                'result' => AssistantToolResult::denied()->toArray(),
                'confirmed_at' => now(),
                'executed_at' => now()
              ])
              ->save();
          }

          return new AssistantToolResult(
            false,
            'This account is no longer allowed to perform that action.',
            [],
            ['execution' => $execution->toClientArray()]
          );
        }

        if ($execution->isExecuted()) {
          return new AssistantToolResult(
            true,
            data_get(
              $execution->result,
              'message',
              'The action was already completed.'
            ),
            data_get($execution->result, 'data', []),
            [
              ...data_get($execution->result, 'meta', []),
              'idempotent_replay' => true,
              'execution' => $execution->toClientArray()
            ]
          );
        }

        if (!$execution->isPending()) {
          return AssistantToolResult::invalid(
            'This assistant action is no longer pending.'
          );
        }

        $executionResult = $tool->executeConfirmed(
          $execution->arguments,
          $context,
          $execution->idempotency_key
        );
        $execution
          ->forceFill([
            'status' => $executionResult->ok ? 'executed' : 'failed',
            'result' => $executionResult->toArray(),
            'confirmed_at' => now(),
            'executed_at' => now()
          ])
          ->save();

        $this->log(
          $executionResult->ok
            ? 'assistant.action.executed'
            : 'assistant.action.failed',
          $executionResult->ok ? 'action_executed' : 'action_failed',
          $conversation,
          $context,
          [
            'tool' => $execution->tool_name,
            'execution_id' => $execution->getKey(),
            'idempotency_key' => $execution->idempotency_key,
            'status' => $execution->status,
            'result' => $executionResult->toArray()
          ]
        );

        return new AssistantToolResult(
          $executionResult->ok,
          $executionResult->message,
          $executionResult->data,
          [
            ...$executionResult->meta,
            'execution' => $execution->toClientArray()
          ]
        );
      });
    } catch (Throwable $exception) {
      Log::error('EduManager AI assistant action confirmation failed.', [
        'conversation_id' => $conversation->getKey(),
        'execution_id' => $executionId,
        'user_id' => $context->userId(),
        'institution_id' => $context->institutionId(),
        'exception' => $exception::class
      ]);

      return new AssistantToolResult(
        false,
        'The action could not be completed safely. Please try again.',
        [],
        ['reason' => 'execution_failed']
      );
    }

    if (!data_get($result->meta, 'idempotent_replay', false)) {
      $executionData = data_get($result->meta, 'execution', []);
      $this->conversations->recordMessage(
        $conversation,
        'assistant',
        $result->message,
        $context,
        [
          'action' => $executionData,
          'action_result' => $result->toArray()
        ],
        [
          [
            'name' => data_get($executionData, 'tool', 'assistant_action'),
            'result' => $result->toArray()
          ]
        ]
      );
    }

    return $result;
  }

  public function cancel(
    AiConversation $conversation,
    string $executionId,
    string $confirmationToken,
    AssistantActorContext $context
  ): AssistantToolResult {
    if (!$this->contextOwnsConversation($conversation, $context)) {
      return AssistantToolResult::denied(
        'This action does not belong to the current assistant conversation.'
      );
    }

    try {
      $result = $this->database->transaction(function () use (
        $conversation,
        $executionId,
        $confirmationToken,
        $context
      ) {
        $execution = AssistantActionExecution::query()
          ->where('conversation_id', $conversation->getKey())
          ->whereKey($executionId)
          ->lockForUpdate()
          ->first();

        if (
          !$execution ||
          (int) $execution->institution_id !==
            (int) $context->institutionId() ||
          !hash_equals(
            $execution->confirmation_token_hash,
            hash('sha256', $confirmationToken)
          )
        ) {
          return AssistantToolResult::denied(
            'This confirmation is no longer valid.'
          );
        }

        if (!$execution->isPending()) {
          return AssistantToolResult::invalid(
            'This assistant action is no longer pending.'
          );
        }

        $execution->forceFill(['status' => 'cancelled'])->save();
        $this->log(
          'assistant.action.cancelled',
          'action_cancelled',
          $conversation,
          $context,
          [
            'tool' => $execution->tool_name,
            'execution_id' => $execution->getKey()
          ]
        );

        return new AssistantToolResult(
          true,
          'The action was cancelled.',
          [],
          [
            'cancelled' => true,
            'execution' => $execution->toClientArray()
          ]
        );
      });
    } catch (Throwable $exception) {
      Log::error('EduManager AI assistant action cancellation failed.', [
        'conversation_id' => $conversation->getKey(),
        'execution_id' => $executionId,
        'user_id' => $context->userId(),
        'institution_id' => $context->institutionId(),
        'exception' => $exception::class
      ]);

      return new AssistantToolResult(
        false,
        'The action could not be cancelled safely. Please try again.',
        [],
        ['reason' => 'cancellation_failed']
      );
    }

    if (!$result->ok || !data_get($result->meta, 'cancelled', false)) {
      return $result;
    }

    $executionData = data_get($result->meta, 'execution', [
      'id' => $executionId,
      'status' => 'cancelled'
    ]);
    $this->conversations->recordMessage(
      $conversation,
      'assistant',
      $result->message,
      $context,
      ['action' => $executionData]
    );

    return $result;
  }

  private function contextOwnsConversation(
    AiConversation $conversation,
    AssistantActorContext $context
  ): bool {
    if (
      $context->isGuest ||
      $context->user === null ||
      $context->institutionId() === null
    ) {
      return false;
    }

    return (int) $conversation->institution_id ===
      (int) $context->institutionId() &&
      (int) $conversation->participant_id === (int) $context->userId() &&
      $conversation->participant_type ===
        AiConversation::participantType($context->user);
  }

  private function log(
    string $event,
    string $action,
    AiConversation $conversation,
    AssistantActorContext $context,
    array $properties
  ): void {
    $this->activityLogger
      ->event($event)
      ->category('authorization')
      ->action($action)
      ->inInstitution($context->institution)
      ->description('Assistant action lifecycle event.')
      ->properties(
        $this->redactor->properties([
          'conversation_id' => $conversation->getKey(),
          'user_id' => $context->userId(),
          ...$properties
        ])
      )
      ->log();
  }
}
