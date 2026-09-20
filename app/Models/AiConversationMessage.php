<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Ai\Models\ConversationMessage;

class AiConversationMessage extends ConversationMessage
{
    protected $guarded = [];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }
}
