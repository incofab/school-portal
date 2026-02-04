<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InternalNotificationTarget extends Model
{
  use HasFactory;

  protected $guarded = [];

  function notification(): BelongsTo
  {
    return $this->belongsTo(InternalNotification::class, 'internal_notification_id');
  }

  function notifiable(): MorphTo
  {
    return $this->morphTo();
  }
}
