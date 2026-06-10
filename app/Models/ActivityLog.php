<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Represents an entry in the system's audit trail.
 * Designed to be append-only with cryptographic integrity verification (HMAC chain).
 */
class ActivityLog extends Model
{
  use HasFactory;

  private static bool $appendOnlyBypass = false;

  protected $guarded = [];

  protected $casts = [
    'institution_id' => 'integer',
    'institution_group_id' => 'integer',
    'properties' => AsArrayObject::class,
    'old_values' => AsArrayObject::class,
    'new_values' => AsArrayObject::class,
    'integrity_verified_at' => 'datetime'
  ];

  protected static function booted(): void
  {
    static::creating(function (self $activityLog) {
      if (blank($activityLog->uuid)) {
        $activityLog->uuid = (string) Str::orderedUuid();
      }

      if ($activityLog->usesTimestamps()) {
        $now = $activityLog->freshTimestamp();

        if (!$activityLog->isDirty($activityLog->getCreatedAtColumn())) {
          $activityLog->setCreatedAt($now);
        }

        if (!$activityLog->isDirty($activityLog->getUpdatedAtColumn())) {
          $activityLog->setUpdatedAt($now);
        }
      }

      $activityLog->severity ??= 'info';

      $activityLog->retention_category ??= self::resolveRetentionCategory(
        $activityLog->category,
        $activityLog->severity
      );
    });

    static::created(function (self $activityLog) {
      if (config('audit.integrity.enabled')) {
        $activityLog->previous_hash ??= self::query()
          ->where('id', '<', $activityLog->id)
          ->latest('id')
          ->value('row_hash');

        $activityLog->row_hash = $activityLog->calculateRowHash();

        self::withoutAppendOnly(fn() => $activityLog->saveQuietly());
      }
    });

    static::updating(function () {
      // Prevent updates to existing logs to maintain audit trail integrity
      return self::$appendOnlyBypass;
    });

    static::deleting(function () {
      // Prevent deletion of existing logs to maintain audit trail integrity
      return self::$appendOnlyBypass;
    });
  }

  public static function withoutAppendOnly(callable $callback): mixed
  {
    $previous = self::$appendOnlyBypass;
    self::$appendOnlyBypass = true;

    try {
      return $callback();
    } finally {
      self::$appendOnlyBypass = $previous;
    }
  }

  public static function resolveRetentionCategory(
    ?string $category,
    ?string $severity = null
  ): string {
    if (
      in_array(
        $category,
        ['fee', 'payment', 'wallet', 'payroll', 'expense'],
        true
      )
    ) {
      return 'financial';
    }

    if (
      in_array(
        $category,
        ['authentication', 'authorization', 'security', 'impersonation'],
        true
      )
    ) {
      return 'security';
    }

    if (in_array($severity, ['security', 'critical'], true)) {
      return 'security';
    }

    return 'normal';
  }

  /**
   * Generates a unique HMAC-SHA256 hash for the record based on its data and the previous record's hash.
   */
  public function calculateRowHash(): string
  {
    $payload = collect([
      'uuid' => $this->uuid,
      'institution_id' => $this->institution_id,
      'institution_group_id' => $this->institution_group_id,
      'actor_type' => $this->actor_type,
      'actor_id' => $this->actor_id,
      'actor_name' => $this->actor_name,
      'actor_role' => $this->actor_role,
      'actor_guard' => $this->actor_guard,
      'action' => $this->action,
      'category' => $this->category,
      'event' => $this->event,
      'subject_type' => $this->subject_type,
      'subject_id' => $this->subject_id,
      'subject_name' => $this->subject_name,
      'description' => $this->description,
      'properties' => $this->propertiesForHash($this->properties),
      'old_values' => $this->propertiesForHash($this->old_values),
      'new_values' => $this->propertiesForHash($this->new_values),
      'ip_address' => $this->ip_address,
      'user_agent' => $this->user_agent,
      'route_name' => $this->route_name,
      'url' => $this->url,
      'method' => $this->method,
      'request_id' => $this->request_id,
      'impersonator_type' => $this->impersonator_type,
      'impersonator_id' => $this->impersonator_id,
      'severity' => $this->severity,
      'retention_category' => $this->retention_category,
      'previous_hash' => $this->previous_hash
    ])
      ->sortKeys()
      ->all();

    return hash_hmac(
      'sha256',
      json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      (string) config('audit.integrity.secret')
    );
  }

  public function verifyIntegrity(?string $expectedPreviousHash = null): bool
  {
    if (!config('audit.integrity.enabled')) {
      return true;
    }

    if (
      $expectedPreviousHash !== null &&
      $this->previous_hash !== $expectedPreviousHash
    ) {
      return false;
    }

    return hash_equals((string) $this->row_hash, $this->calculateRowHash());
  }

  public static function verifyChain($query = null): array
  {
    $query ??= self::query();
    $previousHash = null;
    $checked = 0;

    foreach ((clone $query)->orderBy('id')->cursor() as $activityLog) {
      $checked++;

      if (blank($activityLog->row_hash)) {
        continue;
      }

      if (!$activityLog->verifyIntegrity($previousHash)) {
        return [
          'ok' => false,
          'checked' => $checked,
          'failed_id' => $activityLog->id
        ];
      }

      $previousHash = $activityLog->row_hash;
    }

    return [
      'ok' => true,
      'checked' => $checked,
      'failed_id' => null
    ];
  }

  private function propertiesForHash(mixed $value): mixed
  {
    if ($value instanceof AsArrayObject || $value instanceof Collection) {
      return $this->canonicalizeForHash($value->toArray());
    }

    if ($value instanceof \ArrayObject) {
      return $this->canonicalizeForHash($value->getArrayCopy());
    }

    return $this->canonicalizeForHash($value);
  }

  private function canonicalizeForHash(mixed $value): mixed
  {
    if ($value instanceof Carbon) {
      return $this->dateForHash($value);
    }

    if ($value instanceof \DateTimeInterface) {
      return Carbon::instance($value)->format('Y-m-d H:i:s');
    }

    if ($value instanceof \ArrayObject) {
      $value = $value->getArrayCopy();
    }

    if (!is_array($value)) {
      return $value;
    }

    $isList = array_is_list($value);

    if (!$isList) {
      ksort($value);
    }

    return array_map(fn($item) => $this->canonicalizeForHash($item), $value);
  }

  private function dateForHash(mixed $value): mixed
  {
    if ($value instanceof Carbon) {
      return $value->format('Y-m-d H:i:s');
    }

    if ($value instanceof \DateTimeInterface) {
      return Carbon::instance($value)->format('Y-m-d H:i:s');
    }

    if (is_string($value) && filled($value)) {
      return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    return $value;
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
