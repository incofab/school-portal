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

    public static function sanitizeJsonArray(array $value): array
    {
        $sanitized = self::sanitize($value);
        $jsonSafe = self::jsonSafeValue($sanitized);

        return is_array($jsonSafe) ? $jsonSafe : ['value' => $jsonSafe];
    }

    public static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', ' '], '_', $key));

        return in_array($normalized, self::SENSITIVE_KEYS, true) ||
          str_ends_with($normalized, '_token') ||
          str_ends_with($normalized, '_secret') ||
          str_ends_with($normalized, '_password');
    }

    private static function jsonSafeValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof \Illuminate\Contracts\Support\Arrayable) {
            $value = $value->toArray();
        }

        if ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if (is_array($value)) {
            return collect($value)
                ->mapWithKeys(
                    fn ($item, $key) => [
                        (string) $key => self::jsonSafeValue($item),
                    ]
                )
                ->all();
        }

        if (is_string($value)) {
            return self::jsonSafeString($value);
        }

        if (is_bool($value) || is_int($value) || is_null($value)) {
            return $value;
        }

        if (is_float($value)) {
            return is_finite($value) ? $value : (string) $value;
        }

        if (is_resource($value)) {
            return '[resource]';
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
              ? self::jsonSafeString((string) $value)
              : '[object '.class_basename($value).']';
        }

        return (string) $value;
    }

    private static function jsonSafeString(string $value): string
    {
        $encoded = json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE);

        if ($encoded === false) {
            return '[invalid string]';
        }

        return json_decode($encoded, true);
    }
}
