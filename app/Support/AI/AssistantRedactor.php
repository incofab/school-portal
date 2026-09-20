<?php

namespace App\Support\AI;

use Illuminate\Support\Str;

/**
 * Removes or masks values that must not be written to logs, audit properties,
 * or diagnostics. Applied to anything derived from a user message or a tool
 * argument before it leaves the request.
 */
class AssistantRedactor
{
  /** Keys whose value is never recorded, at any depth. */
  private const DROPPED_KEYS = [
    'password',
    'password_confirmation',
    'confirmation_token',
    'confirmation_token_hash',
    'token',
    'secret',
    'api_key',
    'authorization'
  ];

  /** Keys whose value is recorded only in masked form. */
  private const MASKED_KEYS = ['email', 'phone', 'guest_token'];

  public function properties(array $properties): array
  {
    $redacted = [];

    foreach ($properties as $key => $value) {
      $lowerKey = Str::lower((string) $key);

      if (in_array($lowerKey, self::DROPPED_KEYS, true)) {
        $redacted[$key] = '[redacted]';

        continue;
      }

      if (is_array($value)) {
        $redacted[$key] = $this->properties($value);

        continue;
      }

      $redacted[$key] = in_array($lowerKey, self::MASKED_KEYS, true)
        ? $this->mask((string) $value)
        : $value;
    }

    return $redacted;
  }

  /**
   * Keep just enough of a value to recognise the record in an audit trail
   * without storing the contactable identifier itself.
   */
  public function mask(?string $value): ?string
  {
    if (blank($value)) {
      return $value;
    }

    if (Str::contains($value, '@')) {
      [$local, $domain] = explode('@', $value, 2);

      return Str::substr($local, 0, 2) . str_repeat('*', 4) . '@' . $domain;
    }

    $visible = Str::substr($value, -3);

    return str_repeat('*', max(Str::length($value) - 3, 0)) . $visible;
  }
}
