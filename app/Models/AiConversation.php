<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Ai\Models\Conversation;

class AiConversation extends Conversation
{
    protected $guarded = [];

    protected $casts = [
        'archived_at' => 'datetime',
        'meta' => 'array',
        'summary_updated_at' => 'datetime',
        'entities' => 'array',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(AiConversationMessage::class, 'conversation_id');
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function archive(): void
    {
        $this->forceFill(['archived_at' => Carbon::now()])->save();
    }
}
