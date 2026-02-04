<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InternalNotificationRead extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'internal_notification_id' => 'integer',
    'read_at' => 'datetime',
    'reader_id' => 'integer'
  ];
  protected $appends = ['reader_name'];

  function notification(): BelongsTo
  {
    return $this->belongsTo(
      InternalNotification::class,
      'internal_notification_id'
    );
  }

  function reader(): MorphTo
  {
    return $this->morphTo();
  }

  protected function readerName(): Attribute
  {
    return Attribute::make(
      get: function () {
        $reader = $this->reader;
        if (!$reader) {
          return null;
        }

        $name =
          $reader->getAttribute('full_name') ?? $reader->getAttribute('name');

        if (!$name && method_exists($reader, 'user')) {
          $reader->loadMissing('user');
          $name = $reader->user?->full_name;
        }

        return $name;
      }
    );
  }
}
