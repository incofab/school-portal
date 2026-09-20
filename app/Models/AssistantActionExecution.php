<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantActionExecution extends BaseModel
{
  protected $guarded = [];

  protected $keyType = 'string';

  public $incrementing = false;

  public ?string $confirmation_token = null;

  protected $casts = [
    'arguments' => 'array',
    'preview' => 'array',
    'result' => 'array',
    'confirmed_at' => 'datetime',
    'executed_at' => 'datetime'
  ];

  public function conversation(): BelongsTo
  {
    return $this->belongsTo(AiConversation::class, 'conversation_id');
  }

  public function isPending(): bool
  {
    return $this->status === 'pending';
  }

  public function isExecuted(): bool
  {
    return $this->status === 'executed';
  }

  public function toClientArray(bool $includeToken = false): array
  {
    return [
      'id' => (string) $this->getKey(),
      'tool' => $this->tool_name,
      'status' => $this->status,
      'idempotency_key' => $this->idempotency_key,
      'preview' => $this->preview ?? [],
      'result' => $this->result,
      ...$includeToken
        ? ['confirmation_token' => $this->confirmation_token]
        : []
    ];
  }
}
