<?php

namespace App\Actions\Tutorials;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Restores the database from the snapshot file `CreateDatabaseSnapshot`
 * wrote, by piping the dump back into the `mysql` client. The dump was
 * taken with `--add-drop-table`, so every table it covers is dropped and
 * recreated with exactly the rows/ids it had at snapshot time — this
 * doesn't need to know what a tutorial run created or changed, which is
 * the whole point: no per-tutorial cleanup code required.
 *
 * Deletes the snapshot file once the restore succeeds, so a stale snapshot
 * is never accidentally reused by a later run. If the restore itself
 * fails, the file is deliberately left in place so it can be retried
 * (`php artisan tutorial:db-restore`) instead of losing the only copy of
 * the pre-tutorial state.
 */
class RestoreDatabaseSnapshot
{
  public static function run(): void
  {
    DatabaseSnapshotGuard::assertAllowed();

    $path = config('tutorial.snapshot.path');

    if (!File::exists($path)) {
      throw new RuntimeException(
        "No tutorial database snapshot found at {$path} — nothing to restore."
      );
    }

    $connection = config('database.connections.' . config('database.default'));

    $result = Process::timeout(300)
      ->env(['MYSQL_PWD' => $connection['password']])
      ->input(File::get($path))
      ->run([
        'mysql',
        '-h',
        $connection['host'],
        '-P',
        (string) $connection['port'],
        '-u',
        $connection['username'],
        $connection['database']
      ]);

    if (!$result->successful()) {
      throw new RuntimeException(
        "mysql import failed while restoring the tutorial database snapshot (snapshot kept at {$path} for retry): " .
          DatabaseSnapshotGuard::describeFailure($result)
      );
    }

    File::delete($path);
  }
}
