<?php

namespace App\Http\Controllers\Institutions\Exams\ExamAttempts;

use App\Actions\Exams\PingExamAttemptActivity;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

class PingAttemptController extends Controller
{
  public function __invoke(
    Institution $institution,
    Exam $exam,
    PingExamAttemptActivity $pingExamAttemptActivity
  ) {
    $this->authorizeExam($institution, $exam);

    $pingExamAttemptActivity->execute($exam);

    return $this->ok();
  }

  private function authorizeExam(Institution $institution, Exam $exam): void
  {
    abort_unless($exam->institution_id === $institution->id, 404);

    $user = currentUser();

    if (!$user) {
      return;
    }

    $exam->loadMissing('examable');

    $ownsExam =
      ($exam->examable instanceof User && $exam->examable->id === $user->id) ||
      ($exam->examable instanceof Student &&
        $exam->examable->user_id === $user->id);

    abort_unless($ownsExam, 403, 'You cannot update another exam attempt.');
  }
}
