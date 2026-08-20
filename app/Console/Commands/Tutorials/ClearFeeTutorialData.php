<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\EnsureTutorialInstitution;
use App\Actions\Tutorials\ResetFeeTutorialData;
use Illuminate\Console\Command;

/**
 * Tutorial-only teardown: wipes the fee/payment/bank-account demo data the
 * fee-payment tutorial creates while recording, so nothing it generated
 * sticks around in the database once the video is saved. Run automatically
 * by `tutorials/run.ts` after every "fee-payment" generation (success or
 * failure) — see that file's TUTORIALS registry.
 *
 * Deliberately leaves the demo institution/admin, class, student, and
 * guardian in place — those are long-lived login fixtures reused by every
 * run, not data the simulated demo itself produced (see
 * `EnsureTutorialInstitution`/`SeedFeeTutorialData`).
 */
class ClearFeeTutorialData extends Command
{
  protected $signature = 'tutorial:clear-fee-demo';

  protected $description = 'Wipe the fee/payment/bank-account demo data created while generating the fee-payment tutorial video';

  public function handle(): int
  {
    [, $institution] = EnsureTutorialInstitution::run();

    ResetFeeTutorialData::run($institution);

    $this->info(
      'Fee/payment/bank-account demo data cleared — nothing from this recording was left in the database.'
    );

    return self::SUCCESS;
  }
}
