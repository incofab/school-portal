<?php

namespace App\Support\Audit;

class ActivityLogSanitizer
{
    public const REDACTED = '[redacted]';

    public const SENSITIVE_KEYS = [
        'api_key',
        'apikey',
        'authorization',
        'bvn',
        'card_number',
        'cvv',
        'nin',
        'password',
        'password_confirmation',
        'pin',
        'private_key',
        'remember_token',
        'secret',
        'secret_key',
        'token',
    ];

    public static function sanitize(mixed $value): mixed
    {
        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            $value = $value->toArray();
        }

        if (! is_array($value)) {
            return $value;
        }

        return collect($value)
            ->mapWithKeys(
                fn ($item, $key) => [
                    $key => self::isSensitiveKey((string) $key)
                      ? self::REDACTED
                      : self::sanitize($item),
                ]
            )
            ->all();
    }

    public static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        return in_array($normalized, self::SENSITIVE_KEYS, true) ||
          str_ends_with($normalized, '_token') ||
          str_ends_with($normalized, '_secret') ||
          str_ends_with($normalized, '_password');
    }
}
