<?php

namespace App\Console;

use App\Console\Commands\AssignRoleToUser;
use App\Console\Commands\ProcessSettlements;
use App\Console\Commands\PruneActivityLogs;
use App\Console\Commands\PublishPendingResult;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  /**
   * The Artisan commands provided by your application.
   *
   * @var array
   */
  protected $commands = [
    AssignRoleToUser::class,
    ProcessSettlements::class,
    PublishPendingResult::class,
    PruneActivityLogs::class
  ];

  /**
   * Define the application's command schedule.
   *
   * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
   * @return void
   */
  protected function schedule(Schedule $schedule)
  {
    // $schedule->command('inspire')->hourly();
    $schedule->command('telescope:prune --hours=48')->daily();
    $schedule->command('audit:prune')->dailyAt('02:30');
    $schedule
      ->command('settlements:process')
      ->dailyAt('01:00')
      ->withoutOverlapping();
  }

  /**
   * Register the commands for the application.
   *
   * @return void
   */
  protected function commands()
  {
    $this->load(__DIR__ . '/Commands');

    require base_path('routes/console.php');
  }
}
