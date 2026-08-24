<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Tutorial demo user credentials
  |--------------------------------------------------------------------------
  |
  | Used only by `php artisan tutorial:seed-demo-user` and the Playwright
  | tutorial video generator (tutorials/). Never point these at a real
  | account.
  |
  */

  'demo_email' => env('TUTORIAL_USER_EMAIL', 'tutorial@example.com'),
  'demo_password' => env('TUTORIAL_USER_PASSWORD', 'password'),

  /*
  |--------------------------------------------------------------------------
  | Onboarding-walkthrough demo admin
  |--------------------------------------------------------------------------
  |
  | The school-onboarding-walkthrough tutorial needs a genuinely *empty*
  | school (no classes/subjects/students/teachers), so it uses its own
  | isolated admin/institution instead of the shared one above — see
  | App\Console\Commands\Tutorials\SeedOnboardingTutorialData and
  | App\Actions\Tutorials\EnsureTutorialInstitution.
  |
  */

  'onboarding_demo_email' => env(
    'TUTORIAL_ONBOARDING_USER_EMAIL',
    'tutorial.onboarding.admin@example.com'
  ),

  /*
  |--------------------------------------------------------------------------
  | Database snapshot/restore
  |--------------------------------------------------------------------------
  |
  | Backs `php artisan tutorial:db-snapshot` / `tutorial:db-restore` (see
  | App\Actions\Tutorials\CreateDatabaseSnapshot /
  | App\Actions\Tutorials\RestoreDatabaseSnapshot), which `tutorials/run.ts`
  | wraps around every tutorial-video generation so no tutorial needs its
  | own manual teardown code — the whole database is snapshotted before the
  | run and restored to that exact state after, success or failure.
  |
  | `allowed_environments` is a hard allow-list checked in the Action layer
  | (not just the console command), and App::isProduction() is refused
  | unconditionally regardless of this list — this operation dumps and
  | overwrites the entire database and must never run against production.
  |
  */

  'snapshot' => [
    'allowed_environments' => array_filter(
      explode(',', env('TUTORIAL_SNAPSHOT_ENVIRONMENTS', 'local,testing'))
    ),
    'path' => storage_path('app/tutorial-snapshots/tutorial-db-snapshot.sql')
  ]
];
