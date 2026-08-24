<?php

namespace App\Actions\Tutorials;

use Illuminate\Contracts\Process\ProcessResult;
use RuntimeException;

/**
 * Shared environment guard for `CreateDatabaseSnapshot` /
 * `RestoreDatabaseSnapshot` — both dump/overwrite the *entire* database, so
 * this is checked in the Action layer itself (not just the console
 * commands that call them) to make sure nothing can trigger either one
 * outside an explicitly allowed environment. Production is refused
 * unconditionally, even if `tutorial.snapshot.allowed_environments` were
 * ever misconfigured to include it.
 */
class DatabaseSnapshotGuard
{
  public static function assertAllowed(): void
  {
    if (app()->isProduction()) {
      throw new RuntimeException(
        'Refusing to snapshot/restore the database in production — this operation overwrites the entire database and must never run there.'
      );
    }

    $allowed = config('tutorial.snapshot.allowed_environments', []);

    if (!app()->environment($allowed)) {
      throw new RuntimeException(
        'Refusing to snapshot/restore the database outside of an allowed environment (' .
          implode(', ', $allowed) .
          '). Current environment: ' .
          app()->environment()
      );
    }
  }

  /**
   * A failed mysqldump/mysql run often has empty stdout/stderr — e.g. when
   * the binary itself isn't on PATH, which is exactly what happens if this
   * runs as plain `php artisan` on a host machine whose database is only
   * reachable through Docker (see tutorials/README.md's
   * `TUTORIAL_ARTISAN_COMMAND` prerequisite). Fall back to the exit code
   * plus that hint rather than surfacing a blank, useless error message.
   */
  public static function describeFailure(ProcessResult $result): string
  {
    $detail = trim($result->errorOutput()) ?: trim($result->output());

    if ($detail !== '') {
      return $detail;
    }

    return "exit code {$result->exitCode()}, no output from: {$result->command()} — " .
      'is mysqldump/mysql installed and on PATH, and is that database host actually reachable from here? ' .
      'If this project’s database only runs inside Docker (Sail), run this via `./vendor/bin/sail artisan ...`, not plain `php artisan`.';
  }
}
