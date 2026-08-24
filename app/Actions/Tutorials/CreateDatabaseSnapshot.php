<?php

namespace App\Actions\Tutorials;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Dumps the entire current database to a single file via `mysqldump`, so a
 * tutorial run can later be restored to exactly this state with
 * `RestoreDatabaseSnapshot` — regardless of what data it creates or
 * modifies while recording. This is what lets every tutorial skip writing
 * its own teardown/cleanup code (see `tutorials/run.ts`).
 *
 * Always overwrites the single canonical snapshot file at
 * `config('tutorial.snapshot.path')` — only one tutorial run is ever
 * expected in flight at a time.
 */
class CreateDatabaseSnapshot
{
  public static function run(): string
  {
    DatabaseSnapshotGuard::assertAllowed();

    $connection = config('database.connections.' . config('database.default'));
    $path = config('tutorial.snapshot.path');

    File::ensureDirectoryExists(dirname($path));

    $result = Process::timeout(300)
      ->env(['MYSQL_PWD' => $connection['password']])
      ->run([
        'mysqldump',
        '-h',
        $connection['host'],
        '-P',
        (string) $connection['port'],
        '-u',
        $connection['username'],
        '--single-transaction',
        '--routines',
        '--triggers',
        '--events',
        '--add-drop-table',
        '--no-tablespaces',
        $connection['database']
      ]);

    if (!$result->successful()) {
      throw new RuntimeException(
        'mysqldump failed while creating the tutorial database snapshot: ' .
          DatabaseSnapshotGuard::describeFailure($result)
      );
    }

    File::put($path, $result->output());

    return $path;
  }
}
