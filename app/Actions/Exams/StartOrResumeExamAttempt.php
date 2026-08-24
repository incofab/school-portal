<?php

namespace App\Actions\Exams;

use App\Models\Exam;
use App\Support\ExamHandler;

class StartOrResumeExamAttempt
{
  public function execute(Exam $exam): ExamHandler
  {
    $handler = ExamHandler::make($exam)->startExam();

    $exam
      ->newQuery()
      ->whereKey($exam->id)
      ->whereNull('submitted_at')
      ->update([
        'last_activity_at' => $exam->last_activity_at ?? now(),
        'last_ping_at' => $exam->last_ping_at ?? now()
      ]);

    return $handler;
  }
}
