<?php

namespace App\Actions\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Carbon;

class AttendanceNotificationMessage
{
  public function render(Attendance $attendance, Carbon $date): string
  {
    $student = $attendance->institutionUser?->student;
    $studentName = $student?->user?->full_name ?? 'Student';

    return collect([
      "Attendance update for {$studentName}",
      'Date: ' . $date->toFormattedDateString(),
      'Status: ' . $this->status($attendance),
      'Sign-in: ' . $this->time($attendance->signed_in_at),
      'Sign-out: ' . $this->time($attendance->signed_out_at)
    ])->join(PHP_EOL);
  }

  public function status(Attendance $attendance): string
  {
    if ($attendance->signed_in_at && $attendance->signed_out_at) {
      return 'Signed in and signed out';
    }

    if ($attendance->signed_in_at) {
      return 'Signed in only';
    }

    if ($attendance->signed_out_at) {
      return 'Signed out';
    }

    return 'Attendance recorded';
  }

  private function time(?Carbon $dateTime): string
  {
    return $dateTime?->format('g:i A') ?? 'Not recorded';
  }
}
