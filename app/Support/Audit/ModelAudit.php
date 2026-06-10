<?php

namespace App\Support\Audit;

use Closure;
use Illuminate\Database\Eloquent\Model;

class ModelAudit
{
  private static int $allSuppressionDepth = 0;

  /** @var array<class-string, int> */
  private static array $suppressedModels = [];

  public static function withoutAuditing(Closure $callback): mixed
  {
    self::$allSuppressionDepth++;

    try {
      return $callback();
    } finally {
      self::$allSuppressionDepth--;
    }
  }

  /**
   * @param class-string|array<class-string> $models
   */
  public static function withoutAuditingFor(
    string|array $models,
    Closure $callback
  ): mixed {
    foreach ((array) $models as $model) {
      self::$suppressedModels[$model] =
        (self::$suppressedModels[$model] ?? 0) + 1;
    }

    try {
      return $callback();
    } finally {
      foreach ((array) $models as $model) {
        self::$suppressedModels[$model]--;

        if (self::$suppressedModels[$model] <= 0) {
          unset(self::$suppressedModels[$model]);
        }
      }
    }
  }

  public static function isSuppressed(Model $model): bool
  {
    return self::$allSuppressionDepth > 0 ||
      (self::$suppressedModels[$model::class] ?? 0) > 0;
  }
}
