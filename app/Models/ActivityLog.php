<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'institution_id' => 'integer',
        'institution_group_id' => 'integer',
        'properties' => AsArrayObject::class,
        'old_values' => AsArrayObject::class,
        'new_values' => AsArrayObject::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $activityLog) {
            if (blank($activityLog->uuid)) {
                $activityLog->uuid = (string) Str::orderedUuid();
            }
        });
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function institutionGroup(): BelongsTo
    {
        return $this->belongsTo(InstitutionGroup::class);
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function impersonator(): MorphTo
    {
        return $this->morphTo();
    }
}
