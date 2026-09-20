<?php

namespace App\Services\AI;

use App\DTO\AI\AssistantActorContext;
use App\Models\AiConversation;
use App\Models\AiConversationMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;

class AssistantConversationService
{
  public function create(
    AssistantActorContext $context,
    ?string $guestToken
  ): AiConversation {
    $conversation = new AiConversation();
    $conversation
      ->forceFill([
        'id' => Str::orderedUuid()->toString(),
        'participant_type' => $context->user
          ? AiConversation::participantType($context->user)
          : null,
        'participant_id' => $context->user?->getKey(),
        'institution_id' => $context->institution?->getKey(),
        'guest_token' =>
          $context->isGuest && $guestToken
            ? $this->hashGuestToken($guestToken)
            : null,
        'title' => 'New conversation',
        'meta' => [
          'visibility' => $context->isGuest ? 'public' : 'private',
          'role' => $context->role()
        ]
      ])
      ->save();

    return $conversation;
  }

  /**
   * Return only active conversations owned by the current actor and context.
   */
  public function listFor(
    AssistantActorContext $context,
    ?string $guestToken
  ): array {
    return $this->ownedQuery($context, $guestToken)
      ->active()
      ->latest('updated_at')
      ->limit(config('ai.assistant.max_conversations', 50))
      ->get()
      ->map(fn(AiConversation $conversation) => $this->summary($conversation))
      ->values()
      ->all();
  }

  public function findFor(
    string $conversationId,
    AssistantActorContext $context,
    ?string $guestToken
  ): AiConversation {
    $conversation = $this->ownedQuery($context, $guestToken)
      ->active()
      ->whereKey($conversationId)
      ->first();

    if (!$conversation) {
      throw (new ModelNotFoundException())->setModel(AiConversation::class, [
        $conversationId
      ]);
    }

    return $conversation;
  }

  public function summary(AiConversation $conversation): array
  {
    return [
      'id' => (string) $conversation->getKey(),
      'title' => $conversation->title,
      'updated_at' => $conversation->updated_at?->toIso8601String(),
      'archived_at' => $conversation->archived_at?->toIso8601String(),
      'topic' => $conversation->topic
    ];
  }

  public function details(AiConversation $conversation): array
  {
    $conversation->load([
      'messages' => fn($query) => $query
        ->latest('id')
        ->limit(config('ai.assistant.max_context_messages', 12))
    ]);

    return [
      ...$this->summary($conversation),
      'memory' => [
        'summary' => $conversation->summary,
        'summary_updated_at' => $conversation->summary_updated_at?->toIso8601String(),
        'entities' => $conversation->entities ?? []
      ],
      'messages' => $conversation->messages
        ->sortBy('id')
        ->values()
        ->map(
          fn($message) => [
            'id' => (string) $message->getKey(),
            'role' => $message->role,
            'content' => $message->content,
            'created_at' => $message->created_at?->toIso8601String(),
            'grounded' => (bool) data_get($message->meta, 'grounded', false),
            'sources' => data_get($message->meta, 'sources', []),
            'clarification' => data_get($message->meta, 'clarification'),
            'memory' => data_get($message->meta, 'memory'),
            'tools' => data_get($message->meta, 'tools', []),
            'action' => data_get($message->meta, 'action'),
            'feedback' => data_get($message->meta, 'feedback')
          ]
        )
        ->all()
    ];
  }

  public function rename(
    AiConversation $conversation,
    string $title
  ): AiConversation {
    $conversation->forceFill(['title' => trim($title)])->save();

    return $conversation->fresh();
  }

  /**
   * Permanently remove an owned conversation and its messages. Archiving is
   * the reversible option; this is the explicit "delete my data" path.
   */
  public function delete(AiConversation $conversation): void
  {
    $conversation->messages()->delete();
    $conversation->delete();
  }

  /**
   * Record a rating on one assistant message. Only the rating and an optional
   * short note are stored; the message content is already persisted and no
   * additional personal data is captured.
   */
  public function recordFeedback(
    AiConversation $conversation,
    string $messageId,
    string $rating,
    ?string $note = null
  ): ?AiConversationMessage {
    $message = $conversation
      ->messages()
      ->where('role', 'assistant')
      ->whereKey($messageId)
      ->first();

    if (!$message) {
      return null;
    }

    $message
      ->forceFill([
        'meta' => [
          ...$message->meta ?? [],
          'feedback' => [
            'rating' => $rating,
            'note' => $note ? Str::limit(trim($note), 500, '') : null,
            'recorded_at' => now()->toIso8601String()
          ]
        ]
      ])
      ->save();

    return $message->fresh();
  }

  public function recordMessage(
    AiConversation $conversation,
    string $role,
    string $content,
    AssistantActorContext $context,
    array $meta = [],
    array $toolResults = []
  ): AiConversationMessage {
    $message = $conversation->messages()->create([
      'id' => Str::orderedUuid()->toString(),
      'participant_type' => $context->user
        ? AiConversation::participantType($context->user)
        : null,
      'participant_id' => $context->user?->getKey(),
      'agent' => 'EduManagerAssistant',
      'role' => $role,
      'content' => $content,
      'attachments' => [],
      'tool_calls' => [],
      'tool_results' => $toolResults,
      'usage' => [],
      'meta' => $meta
    ]);

    $conversation->touch();

    return $message;
  }

  public function hashGuestToken(string $guestToken): string
  {
    return hash('sha256', $guestToken);
  }

  private function ownedQuery(
    AssistantActorContext $context,
    ?string $guestToken
  ): Builder {
    $query = AiConversation::query()->when(
      $context->institution,
      fn($query) => $query->where('institution_id', $context->institution->id),
      fn($query) => $query->whereNull('institution_id')
    );

    if ($context->user) {
      return $query
        ->where(
          'participant_type',
          AiConversation::participantType($context->user)
        )
        ->where('participant_id', $context->user->getKey());
    }

    return $query
      ->whereNull('participant_id')
      ->where(
        'guest_token',
        $guestToken ? $this->hashGuestToken($guestToken) : ''
      );
  }
}
