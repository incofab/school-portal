<?php

namespace App\Console\Commands;

use App\Actions\Attendance\SendDailyAttendanceNotifications;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendDailyAttendanceNotificationsCommand extends Command
{
  protected $signature = 'attendance:notify-guardians
    {--date= : Attendance date to process in Y-m-d format}';

  protected $description = 'Send daily student attendance notifications to guardians.';

  public function handle(SendDailyAttendanceNotifications $notifications): int
  {
    $date = $this->option('date')
      ? Carbon::parse($this->option('date'))
      : now();

    $stats = $notifications->run($date);

    $this->info(
      "Attendance notifications complete. {$stats['sent']} sent, {$stats['skipped']} skipped, {$stats['failed']} failed, {$stats['processed']} processed."
    );

    return self::SUCCESS;
  }
}
