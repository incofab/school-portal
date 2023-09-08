<?php
namespace App\Support;

use App\Enums\ExamStatus;
use App\Helpers\ExamAttemptFileHandler;
use App\Models\Exam;
use App\Models\ExamCourseable;
use DB;

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
      return $this;
    }

    $duration = $this->isPaused()
      ? $this->exam->time_remaining
      : $this->exam->event->getDurationInSeconds(); //gets the duration in seconds

    $this->exam
      ->fill([
        'start_time' => $this->exam->start_time ?? now(), //Maintain original start_time
        'pause_time' => null,
        'end_time' => now()->addSecond($duration),
        'time_remaining' => null,
        'status' => ExamStatus::Active
      ])
      ->save();

    ExamAttemptFileHandler::make(
      $this->exam->only([
        'id',
        'event_id',
        'exam_no',
        'time_remaining',
        'start_time',
        'pause_time',
        'end_time',
        'status',
        'num_of_questions'
      ])
    )->syncExamFile();

    return $this;
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
    $this->exam->examCourseables;
    $examAttemptFileHandler = ExamAttemptFileHandler::make($this->exam);
    $totalScore = 0;
    $totalNumOfQuestions = 0;
    DB::beginTransaction();
    /** @var ExamCourseable $examCourseable */
    foreach ($this->exam->examCourseables as $key => $examCourseable) {
      $questions = $examCourseable->courseable->questions()->get();
      $scoreCalc = $examAttemptFileHandler->calculateScoreFromFile($questions);
      $score = $scoreCalc['score'] ?? $examCourseable->score;
      $numOfQuestions = $questions->count();

      $examCourseable
        ->fill([
          'score' => $score,
          'num_of_questions' => $numOfQuestions
        ])
        ->save();
      $totalScore += $score;
      $totalNumOfQuestions += $numOfQuestions;
    }

    $this->exam
      ->fill([
        'pause_time' => null,
        'end_time' => null,
        'time_remaining' => 0,
        'status' => ExamStatus::Ended,
        'score' => $totalScore,
        'num_of_questions' => $totalNumOfQuestions,
        'attempts' => $examAttemptFileHandler->getQuestionAttempts()
      ])
      ->save();
    // $examAttemptFileHandler->deleteExamFile();
    DB::commit();
  }

  function endAndAbort($reason = null)
  {
    $this->endExam();
    abort(401, $reason ?? 'Exam has ended');
  }

  function hasSomeTimeRemaining()
  {
    $timeRemaining = $this->getTimeRemaining();
    return $timeRemaining > 5;
  }

  function getTimeRemaining()
  {
    return now()->diffInSeconds($this->exam->end_time, true);
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
