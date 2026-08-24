<?php

namespace App\Actions\Exams;

use App\Enums\ExamStatus;
use App\Models\Exam;

class PingExamAttemptActivity
{
  public function execute(Exam $exam): void
  {
    abort_if(
      $exam->submitted_at || $exam->status === ExamStatus::Ended,
      409,
      'This exam has already been submitted.'
    );

    $exam
      ->newQuery()
      ->whereKey($exam->id)
      ->whereNull('submitted_at')
      ->update(['last_ping_at' => now()]);
  }
}
