<?php

namespace App\Models;

use App\Enums\Media\MediaKind;
use App\Enums\Media\MediaStatus;
use App\Enums\Media\MediaVisibility;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $appends = ['url'];

  protected $casts = [
    'institution_id' => 'integer',
    'uploaded_by_user_id' => 'integer',
    'size' => 'integer',
    'kind' => MediaKind::class,
    'visibility' => MediaVisibility::class,
    'status' => MediaStatus::class,
    'meta' => AsArrayObject::class,
    'uploaded_at' => 'datetime',
    'failed_at' => 'datetime'
  ];

  protected static function booted(): void
  {
    static::creating(function (self $media) {
      if (blank($media->uuid)) {
        $media->uuid = (string) Str::orderedUuid();
      }
    });
  }

  public function getUrlAttribute(): string
  {
    return Storage::disk($this->disk)->url($this->path);
  }

  public function institution(): BelongsTo
  {
    return $this->belongsTo(Institution::class);
  }

  public function uploadedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'uploaded_by_user_id');
  }

  public function mediable(): MorphTo
  {
    return $this->morphTo();
  }
}
