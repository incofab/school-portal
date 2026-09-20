<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantRunMetric extends BaseModel
{
  protected $guarded = [];

  protected $casts = [
    'is_guest' => 'boolean',
    'grounded' => 'boolean',
    'tools_used' => 'array'
  ];

  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }

  public function conversation(): BelongsTo
  {
    return $this->belongsTo(AiConversation::class, 'conversation_id');
  }
}
