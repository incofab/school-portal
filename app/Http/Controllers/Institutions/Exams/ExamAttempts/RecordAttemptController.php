<?php

namespace App\Http\Controllers\Institutions\Exams\ExamAttempts;

use App\Actions\Exams\RecordExamQuestionAttempt;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exams\RecordExamQuestionAttemptRequest;
use App\Models\Exam;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;

class RecordAttemptController extends Controller
{
  public function __invoke(
    Institution $institution,
    Exam $exam,
    RecordExamQuestionAttemptRequest $request,
    RecordExamQuestionAttempt $recordExamQuestionAttempt
  ) {
    $this->authorizeExam($institution, $exam);

    $records = $recordExamQuestionAttempt->execute(
      $exam,
      $request->validated('attempts'),
      $request->has('current_question_index')
        ? $request->integer('current_question_index')
        : null
    );

    return $this->ok([
      'saved' => $records
        ->map(
          fn($record) => [
            'question_id' => $record['question']->id,
            'question_type' => $record['question']->getMorphClass()
          ]
        )
        ->values()
    ]);
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
