<?php

namespace App\Console\Commands\Tutorials;

use App\Actions\Tutorials\EnsureTutorialInstitution;
use Illuminate\Console\Command;

/**
 * Deliberately the thinnest seed command in tutorials/ — the whole point of
 * the school-onboarding-walkthrough tutorial is a *brand-new, empty* school,
 * so this only ensures the admin/institution themselves exist. Every class,
 * subject, teacher, student, and guardian shown in that recording is
 * created live, on screen, by the tutorial script itself — not seeded here.
 *
 * Uses its own isolated admin/institution (not the shared "Tutorial Demo
 * Academy" used by the other tutorial seed commands) so the school's Setup
 * Checklist genuinely starts fully incomplete, regardless of what data
 * other tutorials have left in the shared demo institution.
 */
class SeedOnboardingTutorialData extends Command
{
  protected $signature = 'tutorial:seed-onboarding-demo';

  protected $description = 'Create the deterministic, empty new-school admin/institution used to generate the school-onboarding walkthrough tutorial video';

  const DEMO_INSTITUTION_NAME = 'New Horizon Academy';

  public function handle(): int
  {
    [$user, $institution] = EnsureTutorialInstitution::run(
      config('tutorial.onboarding_demo_email'),
      self::DEMO_INSTITUTION_NAME
    );

    $this->info(
      "Onboarding demo admin ready: {$user->email} / " .
        config('tutorial.demo_password')
    );
    $this->info(
      "Institution ready (empty, unconfigured): {$institution->name}"
    );

    return self::SUCCESS;
  }
}
