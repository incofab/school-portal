<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\CreateDatabaseSnapshot;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Generic first half of the tutorial database lifecycle — see
 * `tutorial:db-restore` and `tutorials/run.ts`, which runs this before every
 * tutorial-video generation and the restore command after, so no individual
 * tutorial needs its own teardown/cleanup command.
 */
class SnapshotTutorialDatabase extends Command
{
  protected $signature = 'tutorial:db-snapshot';

  protected $description = 'Dump the entire current database so it can be restored exactly with tutorial:db-restore';

  public function handle(): int
  {
    try {
      $path = CreateDatabaseSnapshot::run();
    } catch (RuntimeException $exception) {
      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    $this->info("Database snapshot created: {$path}");

    return self::SUCCESS;
  }
}
