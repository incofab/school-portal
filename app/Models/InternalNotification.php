<?php

namespace App\Models;

use App\Support\Notifications\NotificationViewer;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InternalNotification extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'sender_id' => 'integer',
    'data' => AsArrayObject::class
  ];

  protected $appends = ['sender_name'];

  function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }

  function sender(): MorphTo
  {
    return $this->morphTo();
  }

  function targets(): HasMany
  {
    return $this->hasMany(InternalNotificationTarget::class);
  }

  function reads(): HasMany
  {
    return $this->hasMany(InternalNotificationRead::class);
  }

  function scopeForViewer($query, NotificationViewer $viewer)
  {
    return $query->whereHas('targets', function ($targetQuery) use ($viewer) {
      $targetQuery->where(function ($orQuery) use ($viewer) {
        foreach ($viewer->targets as [$type, $id]) {
          $orQuery->orWhere(function ($innerQuery) use ($type, $id) {
            $innerQuery
              ->where('notifiable_type', $type)
              ->where('notifiable_id', $id);
          });
        }
      });
    });
  }

  function scopeUnreadForViewer($query, NotificationViewer $viewer)
  {
    return $query
      ->forViewer($viewer)
      ->whereDoesntHave('reads', function ($readQuery) use ($viewer) {
        $readQuery
          ->where('reader_type', $viewer->readerType)
          ->where('reader_id', $viewer->readerId);
      });
  }

  static function unreadCountForViewer(NotificationViewer $viewer): int
  {
    return self::query()->unreadForViewer($viewer)->count();
  }

  static function markAllAsRead(NotificationViewer $viewer): int
  {
    $ids = self::query()->unreadForViewer($viewer)->pluck('id');
    if ($ids->isEmpty()) {
      return 0;
    }

    $now = now();
    $rows = $ids
      ->map(fn($id) => [
        'internal_notification_id' => $id,
        'reader_type' => $viewer->readerType,
        'reader_id' => $viewer->readerId,
        'read_at' => $now,
        'created_at' => $now,
        'updated_at' => $now
      ])
      ->all();

    InternalNotificationRead::query()->upsert(
      $rows,
      ['internal_notification_id', 'reader_type', 'reader_id'],
      ['read_at', 'updated_at']
    );

    return count($rows);
  }

  protected function senderName(): Attribute
  {
    return Attribute::make(
      get: function () {
        $sender = $this->sender;
        if (!$sender) {
          return null;
        }

        $name = $sender->getAttribute('full_name') ??
          $sender->getAttribute('name');

        if (!$name && method_exists($sender, 'user')) {
          $sender->loadMissing('user');
          $name = $sender->user?->full_name;
        }

        return $name;
      }
    );
  }
}
