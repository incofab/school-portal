<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\RestoreDatabaseSnapshot;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Generic second half of the tutorial database lifecycle — see
 * `tutorial:db-snapshot` and `tutorials/run.ts`, which runs the snapshot
 * command before every tutorial-video generation and this one after
 * (success or failure), so no individual tutorial needs its own
 * teardown/cleanup command.
 */
class RestoreTutorialDatabase extends Command
{
  protected $signature = 'tutorial:db-restore';

  protected $description = 'Restore the database from the snapshot taken by tutorial:db-snapshot, undoing everything a tutorial run changed';

  public function handle(): int
  {
    try {
      RestoreDatabaseSnapshot::run();
    } catch (RuntimeException $exception) {
      $this->error($exception->getMessage());

      return self::FAILURE;
    }

    $this->info('Database restored to its pre-tutorial snapshot.');

    return self::SUCCESS;
  }
}
