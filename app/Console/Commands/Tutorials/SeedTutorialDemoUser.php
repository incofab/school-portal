<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\EnsureTutorialInstitution;
use Illuminate\Console\Command;

class SeedTutorialDemoUser extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'tutorial:seed-demo-user';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Create or update the deterministic institution admin used to generate tutorial walkthrough videos';

  public function handle(): int
  {
    [$user, $institution] = EnsureTutorialInstitution::run();

    $this->info(
      "Tutorial demo user ready: {$user->email} / " .
        config('tutorial.demo_password')
    );
    $this->info("Institution: {$institution->name} ({$institution->uuid})");

    return self::SUCCESS;
  }
}
