<?php
namespace App\Support;

use App\Enums\ExamStatus;
use App\Models\Exam;
use Illuminate\Validation\ValidationException;

class ExamHandler
{
  function __construct(private Exam $exam)
  {
  }

  static function make(Exam $exam)
  {
    return new self($exam);
  }

  function canRun()
  {
    if ($this->isPending()) {
      return true;
    }
    if ($this->isPaused()) {
      return true;
    }
    if ($this->isEnded()) {
      return false;
    }

    // Getting her means its active
    if ($this->hasSomeTimeRemaining()) {
      return true;
    }

    $this->endAndAbort('Time Elapsed');
    return false;
  }

  function startExam()
  {
    if (!$this->canRun()) {
      abort(401, 'Exam cannot be started');
      return;
    }

    if ($this->isStarted()) {
      return;
    }

    $duration = $this->isPaused()
      ? $this->exam->time_remaining
      : $this->exam->event->duration;

    $this->exam
      ->fill([
        'start_time' => $this->exam->start_time ?? now(), //Maintain original start_time
        'pause_time' => null,
        'end_time' => now()->addSecond($duration),
        'time_remaining' => null,
        'status' => ExamStatus::Active
      ])
      ->save();
  }

  function pauseExam()
  {
    if ($this->isEnded() || $this->isPaused()) {
      return;
    }

    if (!$this->hasSomeTimeRemaining()) {
      $this->endAndAbort('Time has elapsed');
      return;
    }

    $timeRemaining = now()->diffInSeconds($this->exam->end_time, true);

    $this->exam
      ->fill([
        'pause_time' => now(),
        'end_time' => null,
        'time_remaining' => $timeRemaining,
        'status' => ExamStatus::Paused
      ])
      ->save();
  }

  function endExam()
  {
    if ($this->isEnded()) {
      return;
    }
    $this->exam
      ->fill([
        'pause_time' => null,
        'end_time' => null,
        'time_remaining' => 0,
        'status' => ExamStatus::Ended
      ])
      ->save();
  }

  function endAndAbort($reason = null)
  {
    $this->endExam();
    abort(401, $reason ?? 'Exam has ended');
  }

  function hasSomeTimeRemaining()
  {
    $timeRemaining = now()->diffInSeconds($this->exam->end_time, true);
    return $timeRemaining > 5;
  }

  function isPending()
  {
    return $this->exam->status === ExamStatus::Pending;
  }

  function isPaused()
  {
    return $this->exam->status === ExamStatus::Paused;
  }

  function isStarted()
  {
    return $this->exam->status === ExamStatus::Active;
  }

  function isEnded()
  {
    return $this->exam->status === ExamStatus::Ended;
  }
}
